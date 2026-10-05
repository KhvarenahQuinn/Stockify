<?php

namespace Database\Seeders;

use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the application's warehouse products.
     */
    public function run(): void
    {
        $products = [
            [
                'sku' => 'PKG-BOX-S',
                'name' => 'Corrugated Carton Box Small',
                'category' => 'packaging-materials',
                'supplier' => 'SUP-001',
                'buy_price' => 3500,
                'sell_price' => 5000,
                'min_stock' => 100,
                'current_stock' => 240,
                'unit' => 'piece',
                'description' => 'Small single-wall corrugated carton for order packing.',
                'attributes' => ['Color' => 'Brown', 'Size' => 'Small', 'Weight' => '250 g'],
            ],
            [
                'sku' => 'PKG-BOX-L',
                'name' => 'Corrugated Carton Box Large',
                'category' => 'packaging-materials',
                'supplier' => 'SUP-001',
                'buy_price' => 7200,
                'sell_price' => 9500,
                'min_stock' => 60,
                'current_stock' => 118,
                'unit' => 'piece',
                'description' => 'Large double-wall corrugated carton for bulky goods.',
                'attributes' => ['Color' => 'Brown', 'Size' => 'Large', 'Weight' => '500 g'],
            ],
            [
                'sku' => 'PKG-TAPE-CLR',
                'name' => 'Clear Packing Tape 48 mm',
                'category' => 'packaging-materials',
                'supplier' => 'SUP-001',
                'buy_price' => 8500,
                'sell_price' => 12000,
                'min_stock' => 48,
                'current_stock' => 96,
                'unit' => 'roll',
                'description' => 'Heavy-duty clear carton sealing tape.',
                'attributes' => ['Color' => 'White', 'Size' => 'Standard', 'Weight' => '250 g'],
            ],
            [
                'sku' => 'PKG-WRAP-500',
                'name' => 'Stretch Wrap 500 mm',
                'category' => 'packaging-materials',
                'supplier' => 'SUP-001',
                'buy_price' => 42000,
                'sell_price' => 56000,
                'min_stock' => 24,
                'current_stock' => 42,
                'unit' => 'roll',
                'description' => 'Transparent stretch film for securing pallet loads.',
                'attributes' => ['Color' => 'White', 'Size' => 'Standard', 'Weight' => '1 kg'],
            ],
            [
                'sku' => 'SAFE-GLOVE-M',
                'name' => 'Nitrile Safety Gloves Medium',
                'category' => 'safety-equipment',
                'supplier' => 'SUP-002',
                'buy_price' => 28000,
                'sell_price' => 38000,
                'min_stock' => 30,
                'current_stock' => 55,
                'unit' => 'pair',
                'description' => 'Reusable nitrile-coated gloves for general handling.',
                'attributes' => ['Color' => 'Blue', 'Size' => 'Medium', 'Weight' => '250 g'],
            ],
            [
                'sku' => 'SAFE-GLOVE-XL',
                'name' => 'Nitrile Safety Gloves Extra Large',
                'category' => 'safety-equipment',
                'supplier' => 'SUP-002',
                'buy_price' => 28000,
                'sell_price' => 38000,
                'min_stock' => 25,
                'current_stock' => 37,
                'unit' => 'pair',
                'description' => 'Reusable nitrile-coated gloves in extra-large size.',
                'attributes' => ['Color' => 'Blue', 'Size' => 'XL', 'Weight' => '250 g'],
            ],
            [
                'sku' => 'SAFE-VEST-L',
                'name' => 'High Visibility Vest Large',
                'category' => 'safety-equipment',
                'supplier' => 'SUP-002',
                'buy_price' => 45000,
                'sell_price' => 62000,
                'min_stock' => 20,
                'current_stock' => 32,
                'unit' => 'piece',
                'description' => 'Reflective safety vest for warehouse floor staff.',
                'attributes' => ['Color' => 'Yellow', 'Size' => 'Large', 'Weight' => '500 g'],
            ],
            [
                'sku' => 'SAFE-HELMET',
                'name' => 'Adjustable Safety Helmet',
                'category' => 'safety-equipment',
                'supplier' => 'SUP-002',
                'buy_price' => 68000,
                'sell_price' => 89000,
                'min_stock' => 15,
                'current_stock' => 21,
                'unit' => 'piece',
                'description' => 'Impact-resistant helmet with adjustable suspension.',
                'attributes' => ['Color' => 'Yellow', 'Size' => 'Standard', 'Weight' => '500 g'],
            ],
            [
                'sku' => 'CLN-FLOOR-5L',
                'name' => 'Industrial Floor Cleaner 5 L',
                'category' => 'cleaning-supplies',
                'supplier' => 'SUP-003',
                'buy_price' => 58000,
                'sell_price' => 76000,
                'min_stock' => 12,
                'current_stock' => 18,
                'unit' => 'canister',
                'description' => 'Concentrated cleaner for warehouse floors.',
                'attributes' => ['Color' => 'Green', 'Size' => 'Large', 'Weight' => '5 kg'],
            ],
            [
                'sku' => 'CLN-ABSORB-10',
                'name' => 'Universal Spill Absorbent 10 kg',
                'category' => 'cleaning-supplies',
                'supplier' => 'SUP-003',
                'buy_price' => 125000,
                'sell_price' => 159000,
                'min_stock' => 8,
                'current_stock' => 13,
                'unit' => 'bag',
                'description' => 'Granular absorbent for minor liquid spills.',
                'attributes' => ['Color' => 'Gray', 'Size' => 'Standard', 'Weight' => '10 kg'],
            ],
            [
                'sku' => 'TOOL-HANDTRUCK',
                'name' => 'Folding Platform Hand Truck',
                'category' => 'warehouse-tools',
                'supplier' => 'SUP-002',
                'buy_price' => 485000,
                'sell_price' => 625000,
                'min_stock' => 4,
                'current_stock' => 7,
                'unit' => 'piece',
                'description' => 'Compact folding hand truck for moving cartons.',
                'attributes' => ['Color' => 'Black', 'Size' => 'Large', 'Weight' => '20 kg'],
            ],
            [
                'sku' => 'TOOL-CUTTER',
                'name' => 'Retractable Safety Utility Cutter',
                'category' => 'warehouse-tools',
                'supplier' => 'SUP-002',
                'buy_price' => 18500,
                'sell_price' => 26000,
                'min_stock' => 20,
                'current_stock' => 34,
                'unit' => 'piece',
                'description' => 'Retractable blade cutter for opening cartons safely.',
                'attributes' => ['Color' => 'Black', 'Size' => 'Small', 'Weight' => '250 g'],
            ],
        ];

        foreach ($products as $data) {
            $category = Category::where('slug', $data['category'])->firstOrFail();
            $supplier = Supplier::where('code', $data['supplier'])->firstOrFail();
            $attributes = $data['attributes'];
            unset($data['category'], $data['supplier'], $data['attributes']);

            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                [...$data, 'category_id' => $category->id, 'supplier_id' => $supplier->id]
            );

            $valueIds = collect($attributes)->map(function (string $value, string $name): int {
                return AttributeValue::where('value', $value)
                    ->whereHas('attribute', fn ($query) => $query->where('name', $name))
                    ->firstOrFail()
                    ->id;
            });

            $product->attributeValues()->sync($valueIds);
        }
    }
}
