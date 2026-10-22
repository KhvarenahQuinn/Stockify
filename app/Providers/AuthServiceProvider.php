<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Product::class => \App\Policies\ProductPolicy::class,
        \App\Models\StockTransaction::class => \App\Policies\TransactionPolicy::class,
        \App\Models\StockOpname::class => \App\Policies\StockOpnamePolicy::class,
        \App\Models\Category::class => \App\Policies\CategoryPolicy::class,
        \App\Models\Supplier::class => \App\Policies\SupplierPolicy::class,
        \App\Models\Attribute::class => \App\Policies\AttributePolicy::class,
        \App\Models\ActivityLog::class => \App\Policies\ReportPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
