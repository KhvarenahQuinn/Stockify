<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService extends BaseService
{
    protected readonly UserRepository $userRepository;

    public function __construct(
        UserRepository $repository,
        protected readonly ActivityLoggerService $activityLogger,
    ) {
        parent::__construct($repository);
        $this->userRepository = $repository;
    }

    /**
     * List users paginated with eager-loaded role, optionally narrowed by a
     * free-text search and a role name. Callers must validate `$role` against
     * the known role names before it reaches the query.
     *
     * Admin users cannot see Superadmin accounts in the listing.
     */
    public function listUsers(int $perPage = 15, ?string $search = null, ?string $role = null): LengthAwarePaginator
    {
        $query = User::query()
            ->with('role')
            // The delete dialog needs to know, per row, whether the ledger still
            // references the account. Counting here avoids four queries per row.
            ->withCount(['stockTransactions', 'approvedTransactions', 'stockOpnames', 'auditedSheets'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }));

        // Admin users cannot see Superadmin accounts in the listing.
        if (auth()->user()?->role?->name !== 'superadmin') {
            $query->where('users.role_id', '!=',
                Role::where('name', 'superadmin')->value('id')
            );
        }

        return $query
            ->when($role, fn ($query) => $query->whereHas('role', fn ($query) => $query->where('name', $role)))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Find a user by ID with role loaded.
     */
    public function findUser(int $id): User
    {
        return User::query()
            ->with('role')
            ->findOrFail($id);
    }

    /**
     * Create a new user with hashed password.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createUser(array $attributes): User
    {
        if (! empty($attributes['password'])) {
            $attributes['password'] = Hash::make($attributes['password']);
        }

        /** @var User $user */
        $user = $this->repository->create($attributes);

        // Log user creation
        $this->activityLogger->log(
            auth()->user(),
            'user.created',
            "Created user {$user->name}",
            [],
            $user->name
        );

        return $user->load('role');
    }

    /**
     * Update an existing user. Re-hashes password if present.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateUser(int $id, array $attributes): User
    {
        /** @var User $user */
        $user = $this->findUser($id);

        // Admin cannot update Superadmin accounts.
        if (auth()->user()?->role?->name === 'admin' && $user->isSuperadmin()) {
            abort(422, __('Admins cannot update Superadmin accounts.'));
        }

        // Demoting or deactivating the only active super administrator would lock
        // every super administrator out of the application.
        if ($user->isSuperadmin() && $this->isLastActiveSuperAdministrator($user)) {
            $nextRoleId = $attributes['role_id'] ?? $user->role_id;
            $nextIsSuperadmin = Role::query()
                ->whereKey($nextRoleId)
                ->where('name', 'superadmin')
                ->exists();
            $nextIsActive = ($attributes['status'] ?? $user->status) === 'active';

            abort_if(
                ! $nextIsSuperadmin || ! $nextIsActive,
                422,
                __('The last active super administrator cannot be deactivated or demoted.'),
            );
        }

        // Demoting or deactivating the only active administrator would lock
        // every administrator out of the application.
        if ($this->isLastActiveAdministrator($user)) {
            $nextRoleId = $attributes['role_id'] ?? $user->role_id;
            $nextIsAdmin = Role::query()
                ->whereKey($nextRoleId)
                ->where('name', 'admin')
                ->exists();
            $nextIsActive = ($attributes['status'] ?? $user->status) === 'active';

            abort_if(
                ! $nextIsAdmin || ! $nextIsActive,
                422,
                __('The last active administrator cannot be deactivated or demoted.'),
            );
        }

        // Snapshot before the repository rewrites the row so the audit entry can
        // show which columns moved. Password and remember_token are redacted by
        // the logger, so no credential reaches the log payload.
        $before = $this->activityLogger->snapshot($user);

        $passwordChanged = ! empty($attributes['password']);

        if ($passwordChanged) {
            $attributes['password'] = Hash::make($attributes['password']);
        } else {
            unset($attributes['password']);
        }

        /** @var User $user */
        $user = $this->repository->update($id, $attributes);

        // A password change must not leave an old remember-me cookie valid.
        // Live sessions are handled by the auth.session middleware.
        if ($passwordChanged) {
            $user->forceFill(['remember_token' => Str::random(60)])->save();
        }

        // Deactivation revokes the persistent login as well as the session.
        if (($attributes['status'] ?? $user->status) !== 'active') {
            $user->forceFill(['remember_token' => null])->save();
        }

        // Log user update
        $properties = $this->activityLogger->diff($before, $user);

        // The hash is redacted, so without a note a password-only edit would
        // read as if nothing happened.
        if ($passwordChanged) {
            $properties['note'] = 'Password was changed.';
        }

        $this->activityLogger->log(
            auth()->user(),
            'user.updated',
            "Updated user {$user->name}",
            $properties,
            $user->name
        );

        return $user->load('role');
    }

    /**
     * Reset a user's password.
     */
    public function resetPassword(int $id, string $newPlainPassword): User
    {
        $user = $this->findUser($id);

        $this->repository->updatePassword($id, Hash::make($newPlainPassword));

        // Log password reset. The new password is never recorded; the note is
        // what makes the reset visible in the audit trail.
        $this->activityLogger->log(
            auth()->user(),
            'user.password_reset',
            "Reset password for user {$user->name}",
            ['note' => 'Password was reset.'],
            $user->name
        );

        return $user;
    }

    /**
     * Toggle the user's active/inactive status.
     */
    public function toggleUserStatus(int $id): User
    {
        /** @var User $user */
        $user = $this->findUser($id);

        // Admin cannot toggle Superadmin status.
        if (auth()->user()?->role?->name === 'admin' && $user->isSuperadmin()) {
            abort(422, __('Admins cannot toggle Superadmin status.'));
        }

        abort_if(
            $this->isLastActiveAdministrator($user),
            422,
            __('The last active administrator cannot be deactivated.'),
        );

        $before = $this->activityLogger->snapshot($user);

        /** @var User $user */
        $user = $this->repository->toggleStatus($id);

        // Log status toggle
        $action = $user->status === 'active' ? 'user.activated' : 'user.deactivated';
        $this->activityLogger->log(
            auth()->user(),
            $action,
            "Toggled user {$user->name} status to {$user->status}",
            $this->activityLogger->diff($before, $user),
            $user->name
        );

        return $user->load('role');
    }

    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->repository->findByEmail($email);
    }

    /**
     * Inventory records that point at this account through a RESTRICT foreign
     * key. While any of these exist the row cannot be deleted: the database
     * refuses, and a raw failure would read as a server error in the UI.
     *
     * @return array<string, int>
     */
    public function deletionBlockers(User $user): array
    {
        return [
            'transactions' => $user->stockTransactions()->count(),
            'approvals' => $user->approvedTransactions()->count(),
            'opnames' => $user->stockOpnames()->count(),
            'sheets' => $user->auditedSheets()->count(),
        ];
    }

    /**
     * Delete a user after enforcing the administrator safety rails.
     *
     * The checks live here as well as in the controller so a console command,
     * queued job or future API endpoint cannot bypass them.
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->findUser($id);

        abort_if(
            (int) $user->id === (int) auth()->id(),
            422,
            __('You cannot delete your own account.'),
        );

        // Admin cannot delete Superadmin accounts.
        if (auth()->user()?->role?->name === 'admin' && $user->isSuperadmin()) {
            abort(422, __('Admins cannot delete Superadmin accounts.'));
        }

        abort_if(
            $this->isLastActiveAdministrator($user),
            422,
            __('The last active administrator cannot be deleted.'),
        );

        // Stock movements, counts and count sheets name their author, and those
        // keys are RESTRICT: a delete would come back as a 500 from the driver.
        // Refusing here keeps the ledger honest and the message readable.
        $blockers = array_sum($this->deletionBlockers($user));

        abort_if(
            $blockers > 0,
            422,
            __(
                $blockers === 1
                    ? 'This account is named on 1 stock record. Deactivate it instead: deleting would orphan the history.'
                    : 'This account is named on :count stock records. Deactivate it instead: deleting would orphan the history.',
                ['count' => format_number($blockers)]
            ),
        );

        $before = $this->activityLogger->snapshot($user);

        // The foreign key alone is meaningless in the audit view, so the role
        // travels as its label instead of an id.
        $before['role_id'] = $user->role?->display_name ?? $user->role?->name ?? $before['role_id'] ?? null;

        $deleted = $this->repository->delete($id);

        if ($deleted) {
            $this->activityLogger->log(
                auth()->user(),
                'user.deleted',
                "Deleted user {$user->name}",
                ['old' => $before, 'new' => []],
                $user->name
            );
        }

        return $deleted;
    }

    /**
     * Number of administrators that are currently allowed to sign in.
     */
    public function activeAdministratorCount(): int
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'admin'))
            ->where('status', 'active')
            ->count();
    }

    /**
     * Whether the user is the only active super administrator left.
     */
    public function isLastActiveSuperAdministrator(User $user): bool
    {
        return $user->isSuperadmin()
            && $user->isActive()
            && $this->superAdministratorCount() <= 1;
    }

    /**
     * Number of super administrators that are currently allowed to sign in.
     */
    public function superAdministratorCount(): int
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'superadmin'))
            ->where('status', 'active')
            ->count();
    }

    /**
     * Whether the user is the only active administrator left.
     */
    public function isLastActiveAdministrator(User $user): bool
    {
        return $user->isAdmin()
            && $user->isActive()
            && $this->activeAdministratorCount() <= 1;
    }

    /**
     * Roles for the create/edit form dropdown.
     */
    public function roleOptions(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }
}
