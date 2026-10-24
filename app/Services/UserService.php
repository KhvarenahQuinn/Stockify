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
     */
    public function listUsers(int $perPage = 15, ?string $search = null, ?string $role = null): LengthAwarePaginator
    {
        return User::query()
            ->with('role')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
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
        $this->activityLogger->log(auth()->user(), 'user.created', "Created user {$user->name}");

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
        $this->activityLogger->log(auth()->user(), 'user.updated', "Updated user {$user->name}");

        return $user->load('role');
    }

    /**
     * Reset a user's password.
     */
    public function resetPassword(int $id, string $newPlainPassword): User
    {
        $this->repository->updatePassword($id, Hash::make($newPlainPassword));

        // Log password reset
        $this->activityLogger->log(auth()->user(), 'user.password_reset', "Reset password for user ID {$id}");

        return $this->findUser($id);
    }

    /**
     * Toggle the user's active/inactive status.
     */
    public function toggleUserStatus(int $id): User
    {
        /** @var User $user */
        $user = $this->findUser($id);

        abort_if(
            $this->isLastActiveAdministrator($user),
            422,
            __('The last active administrator cannot be deactivated.'),
        );

        /** @var User $user */
        $user = $this->repository->toggleStatus($id);

        // Log status toggle
        $action = $user->status === 'active' ? 'user.activated' : 'user.deactivated';
        $this->activityLogger->log(auth()->user(), $action, "Toggled user {$user->name} status to {$user->status}");

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

        abort_if(
            $this->isLastActiveAdministrator($user),
            422,
            __('The last active administrator cannot be deleted.'),
        );

        return $this->repository->delete($id);
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
