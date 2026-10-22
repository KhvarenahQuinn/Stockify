<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user): bool
    {
        return $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->role?->name === 'admin';
    }
}