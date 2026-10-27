<?php

namespace App\Services;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SupplierService extends BaseService
{
    protected readonly SupplierRepository $supplierRepository;

    public function __construct(
        SupplierRepository $repository,
        protected readonly ActivityLoggerService $activityLogger,
    ) {
        parent::__construct($repository);
        $this->supplierRepository = $repository;
    }

    public function listSuppliers(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->supplierRepository->withProductsCountPaginated($perPage, $search);
    }

    public function allSuppliers(): Collection
    {
        return $this->supplierRepository->all();
    }

    public function findSupplier(int $id): Supplier
    {
        return $this->supplierRepository->findOrFail($id);
    }

    public function createSupplier(array $attributes): Supplier
    {
        if (empty($attributes['code'])) {
            $attributes['code'] = $this->generateCode();
        }

        $supplier = $this->repository->create($attributes);

        $this->activityLogger->log(
            auth()->user(),
            'supplier.created',
            "Created supplier {$supplier->name} ({$supplier->code})",
            [],
            "{$supplier->name} ({$supplier->code})"
        );

        return $supplier->loadCount('products');
    }

    public function updateSupplier(int $id, array $attributes): Supplier
    {
        $before = $this->activityLogger->snapshot($this->findSupplier($id));

        $supplier = $this->repository->update($id, $attributes);

        $this->activityLogger->log(
            auth()->user(),
            'supplier.updated',
            "Updated supplier {$supplier->name} ({$supplier->code})",
            $this->activityLogger->diff($before, $supplier),
            "{$supplier->name} ({$supplier->code})"
        );

        return $supplier->loadCount('products');
    }

    public function deleteSupplier(int $id): bool
    {
        $supplier = $this->findSupplier($id);
        $name = $supplier->name;
        $code = $supplier->code;
        $before = $this->activityLogger->snapshot($supplier);

        $result = $this->repository->delete($id);

        if ($result) {
            $this->activityLogger->log(
                auth()->user(),
                'supplier.deleted',
                "Deleted supplier {$name} ({$code})",
                ['old' => $before, 'new' => []],
                "{$name} ({$code})"
            );
        }

        return $result;
    }

    public function findByEmail(string $email): ?Supplier
    {
        return $this->supplierRepository->findByEmail($email);
    }

    protected function generateCode(): string
    {
        $prefix = 'SUP-';
        $lastCode = $this->supplierRepository->latestCode();

        if ($lastCode === null) {
            return $prefix.'0001';
        }

        $number = (int) substr($lastCode, strlen($prefix)) + 1;

        return $prefix.str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
