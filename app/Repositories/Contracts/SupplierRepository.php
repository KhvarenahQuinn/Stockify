<?php

namespace App\Repositories\Contracts;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SupplierRepository extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?Supplier;
    
    public function latestCode(): ?string;
    
    public function withProductsCountPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator;
}
