<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttributeRequest;
use App\Http\Requests\StoreAttributeValueRequest;
use App\Http\Requests\UpdateAttributeRequest;
use App\Services\AttributeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public const INDEX_PER_PAGE = 15;

    public function __construct(private readonly AttributeService $service) {}

    public function index(Request $request): View
    {
        $specs = $this->service->listAttributes(self::INDEX_PER_PAGE, $request->string('q')->trim()->value());

        // The detail pane follows the ?attribute= query string, which keeps the
        // selection shareable and lets a read-only visitor use it without JS.
        $selectedId = $request->integer('attribute') ?: null;
        $selected = $selectedId
            ? $specs->getCollection()->firstWhere('id', $selectedId)
            : null;

        if ($selectedId && ! $selected) {
            // The requested attribute is on another page or was deleted; fall
            // back rather than rendering an empty detail pane.
            $selected = $this->service->findAttribute($selectedId)->load('values');
        }

        // Deliberately not named `attributes`: Blade reserves that variable for
        // component attribute bags, and shadowing it breaks <x-form.*> fields.
        // The remove-value dialog warns when products still use a value, so the
        // count travels with the relation instead of being queried per chip.
        $selected?->load(['values' => fn ($query) => $query->withCount('products')]);

        return view('attributes.index', [
            'specs' => $specs,
            'selectedAttribute' => $selected,
        ]);
    }

    public function store(StoreAttributeRequest $request): RedirectResponse
    {
        $attribute = $this->service->createAttribute(
            ['name' => $request->validated('name')],
            $request->validated('values', [])
        );

        return redirect()->route('attributes.index', ['attribute' => $attribute->id])
            ->with('success', __('Attribute :name created.', ['name' => $attribute->name]));
    }

    public function update(UpdateAttributeRequest $request, int $attribute): RedirectResponse
    {
        $this->service->updateAttribute(
            $attribute,
            $request->safe()->only(['name']),
            $request->validated('values', [])
        );

        return redirect()->route('attributes.index', ['attribute' => $attribute])
            ->with('success', __('Attribute updated.'));
    }

    public function destroy(int $attribute): RedirectResponse
    {
        $this->service->deleteAttribute($attribute);

        return redirect()->route('attributes.index')
            ->with('success', __('Attribute deleted.'));
    }

    /**
     * Append one value to an attribute without touching the rest of its list.
     */
    public function storeValue(StoreAttributeValueRequest $request, int $attribute): RedirectResponse
    {
        $value = trim($request->validated('value'));
        $attributeModel = $this->service->findAttribute($attribute);

        if ($attributeModel->values->contains('value', $value)) {
            return back()
                ->withInput()
                ->withErrors(['value' => __('":value" is already on this attribute.', ['value' => $value])]);
        }

        $this->service->addAttributeValue($attribute, $value);

        return redirect()->route('attributes.index', ['attribute' => $attribute])
            ->with('success', __('":value" added to :attribute.', ['value' => $value, 'attribute' => $attributeModel->name]));
    }

    /**
     * Remove a single value, leaving the attribute itself intact.
     */
    public function destroyValue(int $value): RedirectResponse
    {
        $attributeId = $this->service->findAttributeValue($value)->attribute_id;

        $this->service->deleteAttributeValue($value);

        return redirect()->route('attributes.index', ['attribute' => $attributeId])
            ->with('success', __('Value removed.'));
    }
}
