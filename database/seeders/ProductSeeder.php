<?php

namespace Database\Seeders;

use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

/**
 * The catalogue.
 *
 * Each row is self-contained (name included) rather than paired with a parallel
 * name array, so inserting or removing a row cannot silently misalign the
 * names. Stock levels are hand-set rather than randomised because the three
 * status badges must always be represented: a random spread would sometimes
 * seed a table with no low-stock rows, which makes the filters look broken.
 *
 * Must run after CategorySeeder, SupplierSeeder and AttributeSeeder.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');
        $suppliers = Supplier::pluck('id', 'code');

        $products = [
            ['category' => 'packaging-materials', 'supplier' => 'SUP-0001', 'name' => 'Single Wall Carton 30x20x15 cm', 'attributes' => ['Colour' => 'Natural', 'Material' => 'Corrugated board', 'Weight' => '500 g'], 'buy' => 7200, 'sell' => 9500, 'min' => 60, 'stock' => 118, 'unit' => 'piece'],
            ['category' => 'packaging-materials', 'supplier' => 'SUP-0001', 'name' => 'Double Wall Carton 60x40x30 cm', 'attributes' => ['Colour' => 'Natural', 'Material' => 'Corrugated board', 'Weight' => '1 kg'], 'buy' => 14500, 'sell' => 18900, 'min' => 40, 'stock' => 64, 'unit' => 'piece'],
            ['category' => 'packaging-materials', 'supplier' => 'SUP-0001', 'name' => 'Clear Sealing Tape 48 mm', 'attributes' => ['Colour' => 'White', 'Material' => 'Polypropylene', 'Weight' => '250 g'], 'buy' => 8500, 'sell' => 12000, 'min' => 48, 'stock' => 96, 'unit' => 'roll'],
            ['category' => 'packaging-materials', 'supplier' => 'SUP-0001', 'name' => 'Stretch Wrap 500 mm', 'attributes' => ['Colour' => 'Blue', 'Material' => 'Polypropylene', 'Weight' => '5 kg'], 'buy' => 42000, 'sell' => 56000, 'min' => 24, 'stock' => 9, 'unit' => 'roll'],
            ['category' => 'packaging-materials', 'supplier' => 'SUP-0001', 'name' => 'Small Shipping Carton 20x15x10 cm', 'attributes' => ['Colour' => 'Natural', 'Material' => 'Corrugated board', 'Weight' => '250 g'], 'buy' => 3500, 'sell' => 5000, 'min' => 100, 'stock' => 240, 'unit' => 'piece'],
            ['category' => 'safety-equipment', 'supplier' => 'SUP-0002', 'name' => 'Nitrile Gloves Medium', 'attributes' => ['Colour' => 'Blue', 'Material' => 'Nitrile', 'Weight' => '250 g'], 'buy' => 28000, 'sell' => 38000, 'min' => 30, 'stock' => 55, 'unit' => 'pair'],
            ['category' => 'safety-equipment', 'supplier' => 'SUP-0002', 'name' => 'Nitrile Gloves Extra Large', 'attributes' => ['Colour' => 'Blue', 'Material' => 'Nitrile', 'Weight' => '250 g'], 'buy' => 28000, 'sell' => 38000, 'min' => 25, 'stock' => 37, 'unit' => 'pair'],
            ['category' => 'safety-equipment', 'supplier' => 'SUP-0002', 'name' => 'High Visibility Vest Large', 'attributes' => ['Colour' => 'Yellow', 'Material' => 'Polypropylene', 'Weight' => '500 g'], 'buy' => 45000, 'sell' => 62000, 'min' => 20, 'stock' => 6, 'unit' => 'piece'],
            ['category' => 'safety-equipment', 'supplier' => 'SUP-0002', 'name' => 'Safety Helmet Adjustable', 'attributes' => ['Colour' => 'White', 'Material' => 'Aluminium', 'Weight' => '500 g'], 'buy' => 68000, 'sell' => 89000, 'min' => 15, 'stock' => 21, 'unit' => 'piece'],
            ['category' => 'cleaning-supplies', 'supplier' => 'SUP-0003', 'name' => 'Floor Cleaner Concentrate 5 L', 'attributes' => ['Colour' => 'Green', 'Expiry Tracking' => 'Expiry date required', 'Weight' => '5 kg'], 'buy' => 58000, 'sell' => 76000, 'min' => 12, 'stock' => 18, 'unit' => 'canister'],
            ['category' => 'cleaning-supplies', 'supplier' => 'SUP-0003', 'name' => 'Spill Absorbent Granular 10 kg', 'attributes' => ['Colour' => 'Grey', 'Expiry Tracking' => 'Not applicable', 'Weight' => '10 kg'], 'buy' => 125000, 'sell' => 159000, 'min' => 8, 'stock' => 13, 'unit' => 'bag'],
            ['category' => 'cleaning-supplies', 'supplier' => 'SUP-0003', 'name' => 'Multi Surface Cleaner 1 L', 'attributes' => ['Colour' => 'White', 'Expiry Tracking' => 'Not applicable', 'Weight' => '1 kg'], 'buy' => 24000, 'sell' => 32000, 'min' => 25, 'stock' => 4, 'unit' => 'bottle'],
            ['category' => 'cleaning-supplies', 'supplier' => 'SUP-0003', 'name' => 'Degreaser Industrial 5 L', 'attributes' => ['Colour' => 'Blue', 'Expiry Tracking' => 'Batch tracked', 'Weight' => '5 kg'], 'buy' => 92000, 'sell' => 118000, 'min' => 10, 'stock' => 0, 'unit' => 'pail'],
            ['category' => 'warehouse-tools', 'supplier' => 'SUP-0002', 'name' => 'Folding Platform Hand Truck', 'attributes' => ['Colour' => 'Black', 'Material' => 'Stainless steel', 'Load Rating' => '150 kg'], 'buy' => 485000, 'sell' => 625000, 'min' => 4, 'stock' => 7, 'unit' => 'piece'],
            ['category' => 'warehouse-tools', 'supplier' => 'SUP-0002', 'name' => 'Retractable Safety Cutter', 'attributes' => ['Colour' => 'Black', 'Material' => 'Stainless steel', 'Load Rating' => '50 kg'], 'buy' => 18500, 'sell' => 26000, 'min' => 20, 'stock' => 34, 'unit' => 'piece'],
            ['category' => 'warehouse-tools', 'supplier' => 'SUP-0002', 'name' => 'Pallet Jack Manual 250 kg', 'attributes' => ['Colour' => 'Yellow', 'Material' => 'Polypropylene', 'Load Rating' => '250 kg'], 'buy' => 265000, 'sell' => 342000, 'min' => 6, 'stock' => 11, 'unit' => 'piece'],
            ['category' => 'warehouse-tools', 'supplier' => 'SUP-0002', 'name' => 'Racking Beam Pair 500 kg', 'attributes' => ['Colour' => 'Orange', 'Material' => 'Aluminium', 'Load Rating' => '500 kg'], 'buy' => 1120000, 'sell' => 1450000, 'min' => 2, 'stock' => 2, 'unit' => 'set'],
            ['category' => 'electronics', 'supplier' => 'SUP-0004', 'name' => 'Handheld Barcode Scanner', 'attributes' => ['Colour' => 'Black', 'Power Source' => 'Rechargeable Li-ion'], 'buy' => 1250000, 'sell' => 1680000, 'min' => 8, 'stock' => 19, 'unit' => 'piece'],
            ['category' => 'electronics', 'supplier' => 'SUP-0004', 'name' => 'Thermal Label Printer 300 dpi', 'attributes' => ['Colour' => 'Grey', 'Power Source' => 'Battery 12V'], 'buy' => 2400000, 'sell' => 3100000, 'min' => 5, 'stock' => 8, 'unit' => 'piece'],
            ['category' => 'electronics', 'supplier' => 'SUP-0004', 'name' => 'Fixed Mount Scanner', 'attributes' => ['Colour' => 'Black', 'Power Source' => 'Mains powered'], 'buy' => 1850000, 'sell' => 2350000, 'min' => 4, 'stock' => 0, 'unit' => 'piece'],
            ['category' => 'electronics', 'supplier' => 'SUP-0004', 'name' => 'Forklift Battery 12V', 'attributes' => ['Colour' => 'Black', 'Power Source' => 'Battery 24V'], 'buy' => 890000, 'sell' => 1150000, 'min' => 10, 'stock' => 26, 'unit' => 'piece'],
            ['category' => 'office-supplies', 'supplier' => 'SUP-0005', 'name' => 'A4 Copy Paper 80 gsm', 'attributes' => ['Colour' => 'Blue', 'Material' => 'Polypropylene'], 'buy' => 12500, 'sell' => 18000, 'min' => 60, 'stock' => 210, 'unit' => 'box'],
            ['category' => 'office-supplies', 'supplier' => 'SUP-0005', 'name' => 'Thermal Label Roll 100x150', 'attributes' => ['Colour' => 'White', 'Material' => 'Polypropylene'], 'buy' => 8500, 'sell' => 12000, 'min' => 80, 'stock' => 65, 'unit' => 'box'],
            ['category' => 'raw-materials', 'supplier' => 'SUP-0006', 'name' => 'Aluminium Sheet Roll 1250 mm', 'attributes' => ['Colour' => 'Natural', 'Material' => 'Aluminium', 'Weight' => '25 kg', 'Expiry Tracking' => 'Batch tracked'], 'buy' => 340000, 'sell' => 425000, 'min' => 20, 'stock' => 48, 'unit' => 'roll'],
            ['category' => 'raw-materials', 'supplier' => 'SUP-0006', 'name' => 'Stainless Steel Plate 2 mm', 'attributes' => ['Colour' => 'Grey', 'Material' => 'Stainless steel', 'Weight' => '20 kg', 'Expiry Tracking' => 'Batch tracked'], 'buy' => 780000, 'sell' => 962000, 'min' => 12, 'stock' => 5, 'unit' => 'piece'],
            ['category' => 'cold-chain', 'supplier' => 'SUP-0007', 'name' => 'Insulated Pallet Cover', 'attributes' => ['Colour' => 'White', 'Storage Temperature' => 'Chilled 2-8 C', 'Expiry Tracking' => 'Expiry date required'], 'buy' => 420000, 'sell' => 525000, 'min' => 15, 'stock' => 31, 'unit' => 'piece'],
            ['category' => 'cold-chain', 'supplier' => 'SUP-0007', 'name' => 'Gel Pack Medium', 'attributes' => ['Colour' => 'Blue', 'Storage Temperature' => 'Frozen -18 C', 'Expiry Tracking' => 'Expiry date required'], 'buy' => 385000, 'sell' => 480000, 'min' => 18, 'stock' => 7, 'unit' => 'piece'],
            ['category' => 'cold-chain', 'supplier' => 'SUP-0007', 'name' => 'Temperature Data Logger', 'attributes' => ['Colour' => 'Grey', 'Power Source' => 'Battery 12V', 'Storage Temperature' => 'Controlled 15-25 C'], 'buy' => 195000, 'sell' => 248000, 'min' => 10, 'stock' => 0, 'unit' => 'piece'],
            ['category' => 'spare-parts', 'supplier' => 'SUP-0004', 'name' => 'Forklift Drive Belt', 'attributes' => ['Colour' => 'Grey', 'Power Source' => 'Battery 12V', 'Load Rating' => '1500 kg'], 'buy' => 1650000, 'sell' => 2100000, 'min' => 4, 'stock' => 12, 'unit' => 'piece'],
            ['category' => 'spare-parts', 'supplier' => 'SUP-0004', 'name' => 'Hydraulic Pump Seal Kit', 'attributes' => ['Colour' => 'Black', 'Power Source' => 'Battery 24V', 'Load Rating' => '250 kg'], 'buy' => 920000, 'sell' => 1180000, 'min' => 6, 'stock' => 3, 'unit' => 'piece'],
        ];

        // Per-category counters give SKU-PACK-001, SKU-SAFE-001, ... and stay
        // deterministic across re-seeds because they derive from this run's
        // ordering, not from the current row count in the table.
        $counters = [];

        foreach ($products as $index => $row) {
            $categorySlug = $row['category'];
            $supplierCode = $row['supplier'];

            if (! isset($categories[$categorySlug], $suppliers[$supplierCode])) {
                throw new \RuntimeException("Product row {$index} references an unknown category or supplier.");
            }

            $counters[$categorySlug] = ($counters[$categorySlug] ?? 0) + 1;

            $token = strtoupper(substr(preg_replace('/[^a-z0-9]+/i', '', $categorySlug), 0, 4));
            $sku = 'SKU-'.$token.'-'.str_pad((string) $counters[$categorySlug], 3, '0', STR_PAD_LEFT);

            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $row['name'],
                    'description' => 'Seeded catalogue item for local development.',
                    'category_id' => $categories[$categorySlug],
                    'supplier_id' => $suppliers[$supplierCode],
                    'buy_price' => $row['buy'],
                    'sell_price' => $row['sell'],
                    'min_stock' => $row['min'],
                    'current_stock' => $row['stock'],
                    'unit' => $row['unit'],
                ]
            );

            $valueIds = collect($row['attributes'])->map(function (string $value, string $name): int {
                return AttributeValue::where('value', $value)
                    ->whereHas('attribute', fn ($query) => $query->where('name', $name))
                    ->firstOrFail()
                    ->id;
            });

            $product->attributeValues()->sync($valueIds);
        }
    }
}