<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed the application's product categories.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Packaging Materials', 'slug' => 'packaging-materials', 'description' => 'Boxes and materials for packing goods.'],
            ['name' => 'Safety Equipment', 'slug' => 'safety-equipment', 'description' => 'Personal protective equipment for warehouse operations.'],
            ['name' => 'Cleaning Supplies', 'slug' => 'cleaning-supplies', 'description' => 'Cleaning products and supplies for facilities.'],
            ['name' => 'Warehouse Tools', 'slug' => 'warehouse-tools', 'description' => 'Tools and equipment used in warehouse operations.'],
        ] as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
