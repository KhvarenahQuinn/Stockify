<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    /**
     * Seed the application's product attributes and values.
     */
    public function run(): void
    {
        foreach ([
            'Color' => ['Brown', 'Black', 'Blue', 'Yellow', 'White', 'Green', 'Gray'],
            'Size' => ['Small', 'Medium', 'Large', 'XL', 'Standard'],
            'Weight' => ['250 g', '500 g', '1 kg', '5 kg', '10 kg', '20 kg'],
        ] as $name => $values) {
            $attribute = Attribute::firstOrCreate(['name' => $name]);

            foreach ($values as $value) {
                $attribute->values()->firstOrCreate(['value' => $value]);
            }
        }
    }
}
