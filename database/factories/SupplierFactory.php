<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // The database has a unique index on code and SupplierService
            // derives new ones from the highest existing code, so the sequence
            // has to stay dense and zero-padded to avoid SUP-9 sorting above
            // SUP-10 and colliding on the next generated value.
            'code' => 'SUP-'.fake()->unique()->numerify('####'),
            'name' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            // Matches StoreSupplierRequest's /^[+]?[\d\-\s\(\)]+$/ rule.
            'phone' => sprintf('+62-%02d-555-%04d', fake()->numberBetween(21, 31), fake()->numberBetween(1000, 9999)),
            'address' => fake()->address(),
        ];
    }
}