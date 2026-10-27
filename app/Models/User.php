<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'phone',
        'status',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Stock movements this account submitted.
     */
    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    /**
     * Stock movements this account approved. Kept separately because the
     * foreign key is RESTRICT: an approver cannot be deleted either.
     */
    public function approvedTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class, 'approved_by');
    }

    /**
     * Individual stock counts this account recorded.
     */
    public function stockOpnames(): HasMany
    {
        return $this->hasMany(StockOpname::class);
    }

    /**
     * Count sheets this account submitted as auditor.
     */
    public function auditedSheets(): HasMany
    {
        return $this->hasMany(StockOpnameSheet::class, 'auditor_id');
    }

    /**
     * How many inventory records point at this account through a RESTRICT
     * foreign key. Anything above zero makes the row undeletable at the
     * database level, so the UI and the service both need to know.
     */
    public function ledgerReferenceCount(): int
    {
        return (int) ($this->stock_transactions_count ?? 0)
            + (int) ($this->approved_transactions_count ?? 0)
            + (int) ($this->stock_opnames_count ?? 0)
            + (int) ($this->audited_sheets_count ?? 0);
    }

    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    public function isSuperadmin(): bool
    {
        return $this->role?->name === 'superadmin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
