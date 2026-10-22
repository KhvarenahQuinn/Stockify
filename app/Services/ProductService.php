<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService extends BaseService
{
    public const SKU_PREFIX = 'PRD';

    public const SKU_SEQUENCE_PAD = 3;

    public const STOCK_FILTERS = ['all', 'out', 'low', 'safe'];

    protected readonly ProductRepository $productRepository;

    public function __construct(
        ProductRepository $repository,
        protected readonly ProductImageService $imageService,
        protected readonly ActivityLoggerService $activityLogger,
    ) {
        parent::__construct($repository);
        $this->productRepository = $repository;
    }

    /**
     * The catalogue listing.
     *
     * An unrecognised $stockStatus is normalised to null so a hand-typed query
     * string falls back to "all" rather than silently filtering everything out.
     *
     * @param  'all'|'out'|'low'|'safe'|null  $stockStatus
     */
    public function listProducts(
        int $perPage = 15,
        ?string $search = null,
        ?int $categoryId = null,
        ?string $stockStatus = null
    ): LengthAwarePaginator {
        return $this->productRepository->withRelationsPaginated(
            $perPage,
            $search,
            $categoryId,
            in_array($stockStatus, self::STOCK_FILTERS, true) && $stockStatus !== 'all' ? $stockStatus : null
        );
    }

    public function allProducts(): Collection
    {
        return $this->productRepository->all();
    }

    public function findProduct(int $id): Product
    {
        return $this->productRepository->findOrFail($id);
    }

    /**
     * Create a product inside a transaction: attributes, image and attribute
     * pivot rows are committed together or rolled back as a unit.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $attributeValueIds
     */
    public function createProduct(
        array $attributes,
        array $attributeValueIds = [],
        ?UploadedFile $image = null
    ): Product {
        return DB::transaction(function () use ($attributes, $attributeValueIds, $image) {
            if (empty($attributes['sku'])) {
                $attributes['sku'] = $this->generateSku((int) $attributes['category_id']);
            }

            if ($image !== null) {
                $attributes['image'] = $this->imageService->store($image);
            }

            /** @var Product $product */
            $product = $this->repository->create($attributes);

            $this->syncAttributeValues($product, $attributeValueIds);

            $this->activityLogger->log(
                auth()->user(),
                'product.created',
                "Created product {$product->sku} ({$product->name})"
            );

            return $product->load(['category', 'supplier', 'attributeValues.attribute']);
        });
    }

    /**
     * Update a product inside a transaction. Passing null for the attribute
     * values leaves the existing pivot rows untouched.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>|null  $attributeValueIds
     */
    public function updateProduct(
        int $id,
        array $attributes,
        ?array $attributeValueIds = null,
        ?UploadedFile $image = null
    ): Product {
        return DB::transaction(function () use ($id, $attributes, $attributeValueIds, $image) {
            /** @var Product $product */
            $product = $this->productRepository->findOrFail($id);

            // Honour an explicitly supplied SKU; otherwise keep the existing one
            // so a category change never silently rewrites the SKU. Only legacy
            // rows without a SKU get one generated.
            if (filled($attributes['sku'] ?? null)) {
                $attributes['sku'] = (string) $attributes['sku'];
            } elseif (blank($product->sku)) {
                $attributes['sku'] = $this->generateSku((int) $product->category_id);
            } else {
                unset($attributes['sku']);
            }

            if ($image !== null) {
                $attributes['image'] = $this->imageService->replace($product->image, $image);
            }

            /** @var Product $updated */
            $updated = $this->productRepository->update($id, $attributes);

            if ($attributeValueIds !== null) {
                $this->syncAttributeValues($updated, $attributeValueIds);
            }

            $this->activityLogger->log(
                auth()->user(),
                'product.updated',
                "Updated product {$updated->sku} ({$updated->name})"
            );

            return $updated->load(['category', 'supplier', 'attributeValues.attribute']);
        });
    }

    public function deleteProduct(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            /** @var Product $product */
            $product = $this->productRepository->findOrFail($id);

            $this->imageService->delete($product->image);

            // Pivot rows use cascadeOnDelete, so the association clears itself.
            $deleted = (bool) $this->repository->delete($id);

            if ($deleted) {
                $this->activityLogger->log(
                    auth()->user(),
                    'product.deleted',
                    "Deleted product {$product->sku} ({$product->name})"
                );
            }

            return $deleted;
        });
    }

    /**
     * Build an auto SKU of the form PRD-CAT-001, where CAT is derived from the
     * category slug and 001 is the next free sequence within that category.
     */
    public function generateSku(int $categoryId): string
    {
        $prefix = self::SKU_PREFIX . '-' . $this->categoryToken($categoryId);
        $sequence = $this->productRepository->nextSkuSequence($categoryId);

        do {
            $candidate = $prefix . '-' . str_pad(
                (string) $sequence,
                self::SKU_SEQUENCE_PAD,
                '0',
                STR_PAD_LEFT
            );

            $sequence++;
        } while ($this->productRepository->skuExists($candidate));

        return $candidate;
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->productRepository->findBySku($sku);
    }

    /**
     * Assign the attribute_value rows that make up this product's attributes.
     *
     * @param  array<int, int>  $attributeValueIds
     */
    protected function syncAttributeValues(Product $product, array $attributeValueIds): void
    {
        if ($attributeValueIds === []) {
            $product->attributeValues()->detach();

            return;
        }

        $product->attributeValues()->sync(array_values(array_unique($attributeValueIds)));
    }

    /**
     * Derive a short uppercase token from the category slug for SKU building.
     */
    protected function categoryToken(int $categoryId): string
    {
        $category = Category::query()->find($categoryId);

        $token = Str::upper($category?->slug ?? '');

        $token = (string) preg_replace('/[^A-Z0-9]/', '', $token);

        if ($token === '') {
            return 'GEN';
        }

        return Str::substr($token, 0, 3);
    }
}