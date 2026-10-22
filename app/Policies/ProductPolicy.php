<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // all authenticated roles can view products
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, \App\Models\Product $product): bool
    {
        return true; // all authenticated roles can view individual products
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role?->name === 'warehouse_manager';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, \App\Models\Product $product): bool
    {
        return $user->role?->name === 'warehouse_manager';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, \App\Models\Product $product): bool
    {
        // Based on RBAC matrix: no delete for any role; use status or soft delete if needed
        return false;
    }
}