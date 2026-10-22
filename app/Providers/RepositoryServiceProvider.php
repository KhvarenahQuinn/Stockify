<?php

namespace App\Providers;

use App\Repositories\Contracts\AttributeRepository;
use App\Repositories\Contracts\AttributeValueRepository;
use App\Repositories\Contracts\CategoryRepository;
use App\Repositories\Contracts\ProductRepository;
use App\Repositories\Contracts\StockOpnameRepository;
use App\Repositories\Contracts\StockOpnameSheetRepository;
use App\Repositories\Contracts\StockTransactionRepository;
use App\Repositories\Contracts\SupplierRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\Eloquent\EloquentAttributeRepository;
use App\Repositories\Eloquent\EloquentAttributeValueRepository;
use App\Repositories\Eloquent\EloquentCategoryRepository;
use App\Repositories\Eloquent\EloquentProductRepository;
use App\Repositories\Eloquent\EloquentStockOpnameRepository;
use App\Repositories\Eloquent\EloquentStockOpnameSheetRepository;
use App\Repositories\Eloquent\EloquentStockTransactionRepository;
use App\Repositories\Eloquent\EloquentSupplierRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository implementations.
     */
    public function register(): void
    {
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
        $this->app->bind(CategoryRepository::class, EloquentCategoryRepository::class);
        $this->app->bind(SupplierRepository::class, EloquentSupplierRepository::class);
        $this->app->bind(AttributeRepository::class, EloquentAttributeRepository::class);
        $this->app->bind(AttributeValueRepository::class, EloquentAttributeValueRepository::class);
        $this->app->bind(StockTransactionRepository::class, EloquentStockTransactionRepository::class);
        $this->app->bind(StockOpnameRepository::class, EloquentStockOpnameRepository::class);
        $this->app->bind(StockOpnameSheetRepository::class, EloquentStockOpnameSheetRepository::class);
    }
}
