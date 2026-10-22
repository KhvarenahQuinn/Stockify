<?php

namespace App\Policies;

use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Roles permitted to post goods receipts and goods issues.
     *
     * @var array<int, string>
     */
    protected array $movements = ['warehouse_staff', 'warehouse_manager', 'admin'];

    /**
     * Roles permitted to approve, reject and complete movements.
     *
     * @var array<int, string>
     */
    protected array $approvers = ['warehouse_manager', 'admin'];

    /**
     * Determine whether the user can view any transactions.
     */
    public function viewAny(User $user): bool
    {
        return true; // all authenticated roles can view transactions
    }

    /**
     * Determine whether the user can view the transaction.
     */
    public function view(User $user, StockTransaction $transaction): bool
    {
        return true; // all authenticated roles can view individual transactions
    }

    /**
     * Determine whether the user can submit a movement request.
     */
    public function create(User $user): bool
    {
        // Staff and above create PENDING requests.
        return $this->hasRole($user, $this->movements);
    }

    /**
     * Roles permitted to work the physical inspection queue.
     *
     * @var array<int, string>
     */
    protected array $inspectors = ['warehouse_staff', 'warehouse_manager', 'admin'];

    /**
     * Approve a transaction (custom policy method).
     */
    public function approve(User $user): bool
    {
        return $this->hasRole($user, $this->approvers);
    }

    /**
     * Reject a transaction (custom policy method).
     */
    public function reject(User $user): bool
    {
        return $this->hasRole($user, $this->approvers);
    }

    /**
     * Record a physical count against a submitted request (custom policy method).
     */
    public function inspect(User $user): bool
    {
        return $this->hasRole($user, $this->inspectors);
    }

    /**
     * Open the manager's approval queue (custom policy method).
     */
    public function reviewQueue(User $user): bool
    {
        return $this->hasRole($user, $this->approvers);
    }

    /**
     * Complete a previously approved transaction (custom policy method).
     */
    public function complete(User $user): bool
    {
        return $this->hasRole($user, $this->approvers);
    }

    /**
     * Determine whether the user can delete the transaction.
     */
    public function delete(User $user, StockTransaction $transaction): bool
    {
        return false; // No delete per RBAC matrix; use status changes instead
    }

    /**
     * @param  array<int, string>  $roles
     */
    protected function hasRole(User $user, array $roles): bool
    {
        return in_array($user->role?->name ?? '', $roles, true);
    }
}