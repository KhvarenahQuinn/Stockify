<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SupplierPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any suppliers.
     */
    public function viewAny(User $user): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can view the supplier.
     */
    public function view(User $user, Supplier $supplier): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can create suppliers.
     */
    public function create(User $user): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can update the supplier.
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }

    /**
     * Determine whether the user can delete the supplier.
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->role?->name === 'warehouse_manager' || $user->role?->name === 'admin';
    }
}