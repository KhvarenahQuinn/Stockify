<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Roles that own the catalogue. WORKFLOW.md gives the warehouse manager the
     * day-to-day product work and the administrator the same rights on top of
     * governance; staff only ever read it.
     */
    private const CATALOGUE_WRITERS = ['warehouse_manager', 'admin'];

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
    public function view(User $user, Product $product): bool
    {
        return true; // all authenticated roles can view individual products
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role?->name, self::CATALOGUE_WRITERS, true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        return in_array($user->role?->name, self::CATALOGUE_WRITERS, true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        return in_array($user->role?->name, self::CATALOGUE_WRITERS, true);
    }
}
