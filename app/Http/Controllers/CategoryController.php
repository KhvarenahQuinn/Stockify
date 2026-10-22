<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public const INDEX_PER_PAGE = 15;

    public function __construct(private readonly CategoryService $service) {}

    public function index(Request $request): View
    {
        return view('categories.index', [
            'categories' => $this->service->listCategories(self::INDEX_PER_PAGE, $request->string('q')->trim()->value()),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = $this->service->createCategory($request->safe()->only(['name', 'slug', 'description']));

        return redirect()->route('categories.index')
            ->with('success', __('Category :name created.', ['name' => $category->name]));
    }

    public function update(UpdateCategoryRequest $request, int $category): RedirectResponse
    {
        $this->service->updateCategory($category, $request->safe()->only(['name', 'slug', 'description']));

        return redirect()->route('categories.index')
            ->with('success', __('Category updated.'));
    }

    public function destroy(int $category): RedirectResponse
    {
        $this->service->deleteCategory($category);

        return redirect()->route('categories.index')
            ->with('success', __('Category deleted.'));
    }
}