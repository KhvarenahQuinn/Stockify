<?php

namespace App\Policies;

use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockOpnamePolicy
{
    use HandlesAuthorization;

    /**
     * Roles permitted to record a physical count.
     *
     * @var array<int, string>
     */
    protected array $counters = ['warehouse_staff', 'warehouse_manager', 'admin'];

    /**
     * Roles permitted to verify a count and post the adjustment.
     *
     * @var array<int, string>
     */
    protected array $verifiers = ['warehouse_manager', 'admin'];

    /**
     * Determine whether the user can view any counts.
     */
    public function viewAny(User $user): bool
    {
        return true; // all authenticated roles can view stock opnames
    }

    /**
     * Determine whether the user can view the count.
     */
    public function view(User $user, StockOpname $opname): bool
    {
        return true; // all authenticated roles can view individual counts
    }

    /**
     * Staff input a physical count; the result is always a DRAFT.
     */
    public function create(User $user): bool
    {
        return in_array($user->role?->name ?? '', $this->counters, true);
    }

    /**
     * Verify a count and apply the stock adjustment (custom policy method).
     */
    public function verify(User $user): bool
    {
        return in_array($user->role?->name ?? '', $this->verifiers, true);
    }

    /**
     * Determine whether the user can delete the count.
     */
    public function delete(User $user, StockOpname $opname): bool
    {
        return false; // No delete per RBAC matrix; counts are an audit trail
    }
}