<?php

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReportPolicy
{
    use HandlesAuthorization;

    /**
     * Roles allowed to read reporting data.
     *
     * @var array<int, string>
     */
    protected array $readers = ['admin', 'warehouse_manager', 'warehouse_staff'];

    /**
     * Activity logs are an audit trail; staff see their own entries only.
     */
    public function viewStockReport(User $user): bool
    {
        return in_array($user->role?->name ?? '', $this->readers, true);
    }

    public function viewTransactionReport(User $user): bool
    {
        return in_array($user->role?->name ?? '', $this->readers, true);
    }

    public function viewActivityLogReport(User $user): bool
    {
        return in_array($user->role?->name ?? '', $this->readers, true);
    }

    /**
     * Only a manager or admin sees the full audit trail rather than their own.
     */
    public function viewAllActivity(User $user): bool
    {
        return in_array($user->role?->name ?? '', ['admin', 'warehouse_manager'], true);
    }

    public function export(User $user): bool
    {
        return in_array($user->role?->name ?? '', ['admin', 'warehouse_manager'], true);
    }

    public function manageSettings(User $user): bool
    {
        return $user->role?->name === 'admin';
    }
}
