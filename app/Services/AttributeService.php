<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Repositories\Contracts\AttributeRepository;
use App\Repositories\Contracts\AttributeValueRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AttributeService extends BaseService
{
    protected readonly AttributeRepository $attributeRepository;

    protected readonly AttributeValueRepository $attributeValueRepository;

    public function __construct(
        AttributeRepository $repository,
        AttributeValueRepository $attributeValueRepository,
        protected readonly ActivityLoggerService $activityLogger,
    ) {
        parent::__construct($repository);
        $this->attributeRepository = $repository;
        $this->attributeValueRepository = $attributeValueRepository;
    }

    public function listAttributes(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        return $this->attributeRepository->withValuesPaginated($perPage, $search);
    }

    public function allAttributes(): Collection
    {
        return $this->attributeRepository->all();
    }

    /**
     * Attributes with their values loaded, for forms that render value pickers.
     */
    public function allAttributesWithValues(): Collection
    {
        return $this->attributeRepository->allWithValues();
    }

    public function findAttribute(int $id): Attribute
    {
        return $this->attributeRepository->findOrFail($id);
    }

    public function findAttributeValue(int $id): AttributeValue
    {
        return $this->attributeValueRepository->findOrFail($id);
    }

    public function createAttribute(array $attributes, array $values = []): Attribute
    {
        // The attribute and its values are one unit; a failure mid-way through
        // the inserts would leave an attribute with a partial value set.
        $attribute = DB::transaction(function () use ($attributes, $values) {
            $attribute = $this->repository->create($attributes);

            foreach ($values as $value) {
                $this->attributeValueRepository->create([
                    'attribute_id' => $attribute->id,
                    'value' => $value,
                ]);
            }

            return $attribute;
        });

        $this->activityLogger->log(
            auth()->user(),
            'attribute.created',
            "Created attribute {$attribute->name} with ".count($values).' value(s)',
            [],
            $attribute->name
        );

        return $attribute->load('values');
    }

    public function updateAttribute(int $id, array $attributes, array $values = []): Attribute
    {
        $before = $this->activityLogger->snapshot($this->findAttribute($id));
        $valuesBefore = $this->attributeRepository->valuesForAttribute($id)->pluck('value')->all();

        // deleteByAttribute() runs before the re-inserts, so without a
        // transaction a failure would leave the attribute with no values at all.
        $attribute = DB::transaction(function () use ($id, $attributes, $values) {
            $attribute = $this->repository->update($id, $attributes);

            if ($values !== []) {
                $this->attributeValueRepository->deleteByAttribute($id);

                foreach ($values as $value) {
                    $this->attributeValueRepository->create([
                        'attribute_id' => $attribute->id,
                        'value' => $value,
                    ]);
                }
            }

            return $attribute;
        });

        $changes = $this->activityLogger->diff($before, $attribute);
        $valuesAfter = $this->attributeRepository->valuesForAttribute($id)->pluck('value')->all();

        if ($valuesBefore != $valuesAfter) {
            $changes['old']['values'] = $valuesBefore;
            $changes['new']['values'] = $valuesAfter;
        }

        $this->activityLogger->log(
            auth()->user(),
            'attribute.updated',
            "Updated attribute {$attribute->name} with ".count($values).' value(s)',
            $changes,
            $attribute->name
        );

        return $attribute->load('values');
    }

    public function deleteAttribute(int $id): bool
    {
        $attribute = $this->findAttribute($id);
        $name = $attribute->name;
        $before = $this->activityLogger->snapshot($attribute);
        $before['values'] = $this->attributeRepository->valuesForAttribute($id)->pluck('value')->all();

        $result = DB::transaction(function () use ($id) {
            $this->attributeValueRepository->deleteByAttribute($id);

            return $this->repository->delete($id);
        });

        if ($result) {
            $this->activityLogger->log(
                auth()->user(),
                'attribute.deleted',
                "Deleted attribute {$name}",
                ['old' => $before, 'new' => []],
                $name
            );
        }

        return $result;
    }

    public function findByName(string $name): ?Attribute
    {
        return $this->attributeRepository->findByName($name);
    }

    public function addAttributeValue(int $attributeId, string $value): AttributeValue
    {
        $attribute = $this->findAttribute($attributeId);

        $attributeValue = $this->attributeValueRepository->create([
            'attribute_id' => $attributeId,
            'value' => $value,
        ]);

        $this->activityLogger->log(
            auth()->user(),
            'attribute_value.created',
            "Added value '{$value}' to attribute {$attribute->name}",
            ['new' => ['value' => $value]],
            $attribute->name
        );

        return $attributeValue;
    }

    public function updateAttributeValue(int $attributeValueId, string $value): AttributeValue
    {
        $previous = $this->attributeValueRepository->findOrFail($attributeValueId);
        $attribute = $this->findAttribute((int) $previous->attribute_id);

        $attributeValue = $this->attributeValueRepository->update($attributeValueId, [
            'value' => $value,
        ]);

        $this->activityLogger->log(
            auth()->user(),
            'attribute_value.updated',
            "Renamed value '{$previous->value}' to '{$value}' on attribute {$attribute->name}",
            ['old' => ['value' => $previous->value], 'new' => ['value' => $value]],
            $attribute->name
        );

        return $attributeValue;
    }

    public function deleteAttributeValue(int $attributeValueId): bool
    {
        $attributeValue = $this->attributeValueRepository->findOrFail($attributeValueId);
        $attribute = $this->findAttribute((int) $attributeValue->attribute_id);
        $value = $attributeValue->value;
        $result = $this->attributeValueRepository->delete($attributeValueId);

        if ($result) {
            $this->activityLogger->log(
                auth()->user(),
                'attribute_value.deleted',
                "Deleted value '{$value}' from attribute {$attribute->name}",
                ['old' => ['value' => $value], 'new' => []],
                $attribute->name
            );
        }

        return $result;
    }

    public function valuesForAttribute(int $attributeId): Collection
    {
        return $this->attributeRepository->valuesForAttribute($attributeId);
    }
}
