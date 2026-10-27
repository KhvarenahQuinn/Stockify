<?php

namespace App\Policies;

use App\Models\Attribute;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttributePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any attributes.
     */
    public function viewAny(User $user): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can view the attribute.
     */
    public function view(User $user, Attribute $attribute): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can create attributes.
     */
    public function create(User $user): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can update the attribute.
     */
    public function update(User $user, Attribute $attribute): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can delete the attribute.
     */
    public function delete(User $user, Attribute $attribute): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }
}