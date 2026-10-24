<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Services\ActivityLoggerService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class UserController extends Controller
{
    /**
     * Display a listing of the users, narrowed by the optional `q` and `role`
     * query parameters used by the search bar and role filter.
     */
    public function index(Request $request, UserService $userService): View
    {
        $role = $request->query('role');

        return view('users.index', [
            'users' => $userService->listUsers(
                search: trim((string) $request->query('q', '')) ?: null,
                role: is_string($role) && Role::where('name', $role)->exists() ? $role : null,
            ),
            'roles' => $userService->roleOptions(),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(UserService $userService): View
    {
        return view('users.create', [
            'roles' => $userService->roleOptions(),
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request, UserService $userService, ActivityLoggerService $activityLogger): RedirectResponse
    {
        $user = $userService->createUser($request->validated());
        $activityLogger->log(auth()->user(), 'user.created', "Created user {$user->name}");

        return redirect()->route('admin.users.index')
            ->with('success', __('User created successfully.'));
    }

    /**
     * Display the specified user.
     */
    public function show(int $id, UserService $userService): View
    {
        $user = $userService->findUser($id);

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(int $id, UserService $userService): View
    {
        return view('users.edit', [
            'user' => $userService->findUser($id),
            'roles' => $userService->roleOptions(),
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, int $id, UserService $userService, ActivityLoggerService $activityLogger): RedirectResponse
    {
        try {
            $user = $userService->updateUser($id, $request->validated());
        } catch (HttpExceptionInterface $exception) {
            return $this->guardFailure($exception);
        }

        $activityLogger->log(auth()->user(), 'user.updated', "Updated user {$user->name}");

        return redirect()->route('admin.users.index')
            ->with('success', __('User updated successfully.'));
    }

    /**
     * Toggle the user's active/inactive status.
     */
    public function toggleStatus(int $id, UserService $userService, ActivityLoggerService $activityLogger): RedirectResponse
    {
        try {
            $user = $userService->toggleUserStatus($id);
        } catch (HttpExceptionInterface $exception) {
            return $this->guardFailure($exception);
        }

        $action = $user->status === 'active' ? 'user.activated' : 'user.deactivated';
        $activityLogger->log(auth()->user(), $action, "Toggled user {$user->name} status to {$user->status}");

        return redirect()->route('admin.users.index')
            ->with('success', __('User status updated successfully.'));
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(int $id, UserService $userService, ActivityLoggerService $activityLogger): RedirectResponse
    {
        $user = $userService->findUser($id);
        $name = $user->name;

        try {
            abort_if(
                (int) $user->id === (int) auth()->id(),
                422,
                __('You cannot delete your own account.'),
            );

            $userService->deleteUser($id);
        } catch (HttpExceptionInterface $exception) {
            return $this->guardFailure($exception);
        }

        $activityLogger->log(auth()->user(), 'user.deleted', "Deleted user {$name}");

        return redirect()->route('admin.users.index')
            ->with('success', __('User deleted successfully.'));
    }

    /**
     * Turn a 422 safety-rail abort into a localized toast instead of an error
     * page. Other HTTP errors keep their normal handling.
     */
    private function guardFailure(HttpExceptionInterface $exception): RedirectResponse
    {
        if ($exception->getStatusCode() !== 422) {
            throw $exception;
        }

        return back()->with('error', $exception->getMessage());
    }
}
