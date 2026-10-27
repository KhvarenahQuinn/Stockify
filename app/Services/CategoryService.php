<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CategoryService extends BaseService
{
    protected readonly CategoryRepository $categoryRepository;

    public function __construct(
        CategoryRepository $repository,
        protected readonly ActivityLoggerService $activityLogger,
    ) {
        parent::__construct($repository);
        $this->categoryRepository = $repository;
    }

    public function listCategories(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->categoryRepository->withProductsCountPaginated($perPage, $search);
    }

    public function allCategories(): Collection
    {
        return $this->categoryRepository->all();
    }

    public function findCategory(int $id): Category
    {
        return $this->categoryRepository->findOrFail($id);
    }

    public function createCategory(array $attributes): Category
    {
        if (empty($attributes['slug'])) {
            $attributes['slug'] = $this->generateSlug($attributes['name']);
        }

        $category = $this->repository->create($attributes);

        $this->activityLogger->log(
            auth()->user(),
            'category.created',
            "Created category {$category->name}",
            [],
            $category->name
        );

        return $category->loadCount('products');
    }

    public function updateCategory(int $id, array $attributes): Category
    {
        if (isset($attributes['name']) && empty($attributes['slug'])) {
            $attributes['slug'] = $this->generateSlug($attributes['name'], $id);
        }

        $before = $this->activityLogger->snapshot($this->findCategory($id));

        $category = $this->repository->update($id, $attributes);

        $this->activityLogger->log(
            auth()->user(),
            'category.updated',
            "Updated category {$category->name}",
            $this->activityLogger->diff($before, $category),
            $category->name
        );

        return $category->loadCount('products');
    }

    public function deleteCategory(int $id): bool
    {
        $category = $this->findCategory($id);
        $name = $category->name;
        $before = $this->activityLogger->snapshot($category);

        $result = $this->repository->delete($id);

        if ($result) {
            $this->activityLogger->log(
                auth()->user(),
                'category.deleted',
                "Deleted category {$name}",
                ['old' => $before, 'new' => []],
                $name
            );
        }

        return $result;
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->categoryRepository->findBySlug($slug);
    }

    protected function generateSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        while ($this->categoryRepository->slugExists($slug, $ignoreId)) {
            $slug = $original.'-'.$count++;
        }

        return $slug;
    }
}
