<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role?->name === 'superadmin' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Superadmin can view any user; admin can view any non-superadmin user;
        // users can view themselves
        if ($model->role?->name === 'superadmin') {
            return $user->role?->name === 'superadmin';
        }

        return $user->role?->name === 'superadmin' || $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role?->name === 'superadmin' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Superadmin can update any user
        // Admin cannot update superadmin users
        if ($model->role?->name === 'superadmin') {
            return $user->role?->name === 'superadmin';
        }

        return $user->role?->name === 'admin' || $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Superadmin can delete any user
        // Admin cannot delete superadmin users
        // Admin cannot delete their own account
        if ($model->role?->name === 'superadmin') {
            return $user->role?->name === 'superadmin';
        }

        if ((int) $model->id === (int) auth()->id()) {
            return false;
        }

        return $user->role?->name === 'admin';
    }
}