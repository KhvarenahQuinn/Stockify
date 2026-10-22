<?php

namespace App\Repositories\Eloquent;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentSupplierRepository extends BaseEloquentRepository implements SupplierRepository
{
    public function __construct()
    {
        parent::__construct(Supplier::class);
    }

    public function findByEmail(string $email): ?Supplier
    {
        return $this->modelClass::query()
            ->where('email', $email)
            ->first();
    }

    public function latestCode(): ?string
    {
        return $this->modelClass::query()
            ->where('code', 'like', 'SUP-%')
            ->orderBy('code', 'desc')
            ->value('code');
    }

    public function withProductsCountPaginated(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->modelClass::query()
            ->search($search)
            ->withCount('products')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
