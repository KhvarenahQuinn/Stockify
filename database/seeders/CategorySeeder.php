<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * The inventory taxonomy. Slugs are the lookup key for ProductService's SKU
 * generator, so they are seeded explicitly rather than derived, and every row
 * is keyed on slug via updateOrCreate to keep re-seeding idempotent.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'packaging-materials',
                'name' => 'Packaging Materials',
                'description' => 'Cartons, tape, film and void fill for packing outbound orders.',
            ],
            [
                'slug' => 'safety-equipment',
                'name' => 'Safety Equipment',
                'description' => 'Personal protective equipment for warehouse floor operations.',
            ],
            [
                'slug' => 'cleaning-supplies',
                'name' => 'Cleaning Supplies',
                'description' => 'Cleaning chemicals and consumables for facility maintenance.',
            ],
            [
                'slug' => 'warehouse-tools',
                'name' => 'Warehouse Tools',
                'description' => 'Material handling tools and equipment used on the floor.',
            ],
            [
                'slug' => 'electronics',
                'name' => 'Electronics',
                'description' => 'Scanners, printers, batteries and other powered devices.',
            ],
            [
                'slug' => 'office-supplies',
                'name' => 'Office Supplies',
                'description' => 'Stationery and consumables used by warehouse administration.',
            ],
            [
                'slug' => 'raw-materials',
                'name' => 'Raw Materials',
                'description' => 'Bulk inputs held for assembly and repacking.',
            ],
            [
                'slug' => 'cold-chain',
                'name' => 'Cold Chain',
                'description' => 'Temperature controlled storage items and monitoring kit.',
            ],
            [
                'slug' => 'spare-parts',
                'name' => 'Spare Parts',
                'description' => 'Replacement components for forklifts, conveyors and racking.',
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}