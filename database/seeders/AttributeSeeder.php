<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * Product specifications and their allowed values, plus which categories each
 * specification applies to via the attribute_category pivot.
 *
 * Without the pivot the product form offers every attribute regardless of what
 * is being catalogued; with it, "Storage Temperature" only appears for Cold
 * Chain products. Must run after CategorySeeder.
 */
class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            'Colour' => [
                'values' => ['Natural', 'White', 'Blue', 'Black', 'Yellow', 'Orange', 'Green', 'Grey', 'Red'],
                // Every category: even powered devices carry a colour specification
                // (black or grey scanners, white label printers).
                'categories' => [
                    'packaging-materials', 'safety-equipment', 'cleaning-supplies',
                    'warehouse-tools', 'office-supplies', 'raw-materials',
                    'cold-chain', 'spare-parts', 'electronics',
                ],
            ],
            'Material' => [
                'values' => ['Corrugated board', 'HDPE', 'Stainless steel', 'Polypropylene', 'Cotton', 'Nitrile', 'Aluminium'],
                'categories' => ['packaging-materials', 'safety-equipment', 'warehouse-tools', 'office-supplies', 'raw-materials'],
            ],
            'Weight' => [
                'values' => ['250 g', '500 g', '1 kg', '5 kg', '10 kg', '20 kg', '25 kg'],
                'categories' => ['packaging-materials', 'safety-equipment', 'cleaning-supplies', 'raw-materials', 'cold-chain'],
            ],
            'Storage Temperature' => [
                'values' => ['Ambient', 'Chilled 2-8 C', 'Frozen -18 C', 'Controlled 15-25 C'],
                'categories' => ['cold-chain'],
            ],
            'Expiry Tracking' => [
                'values' => ['Not applicable', 'Batch tracked', 'Expiry date required'],
                'categories' => ['cleaning-supplies', 'raw-materials', 'cold-chain'],
            ],
            'Power Source' => [
                'values' => ['Mains powered', 'Battery 12V', 'Battery 24V', 'Rechargeable Li-ion', 'USB-C'],
                // Cold Chain data loggers are battery powered, so it is linked there too.
                'categories' => ['electronics', 'warehouse-tools', 'spare-parts', 'cold-chain'],
            ],
            'Load Rating' => [
                'values' => ['50 kg', '150 kg', '250 kg', '500 kg', '1500 kg'],
                'categories' => ['warehouse-tools', 'spare-parts'],
            ],
        ];

        foreach ($attributes as $name => $definition) {
            $attribute = Attribute::firstOrCreate(['name' => $name]);

            foreach ($definition['values'] as $value) {
                $attribute->values()->firstOrCreate(['value' => $value]);
            }

            $categoryIds = Category::whereIn('slug', $definition['categories'])->pluck('id');

            if ($categoryIds->count() !== count($definition['categories'])) {
                // Seeding a missing category silently would leave the pivot
                // short and look like a partial seed, so fail loudly instead.
                throw new \RuntimeException(
                    "Attribute \"{$name}\" references unknown categories: "
                    .$definition['categories'][0]
                );
            }

            $attribute->categories()->sync($categoryIds);
        }
    }
}