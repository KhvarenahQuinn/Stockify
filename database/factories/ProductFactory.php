<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $buy = fake()->numberBetween(5_000, 500_000);

        return [
            // products.sku is unique, so it must be unique per generated record.
            'sku' => 'SKU-'.fake()->unique()->bothify('????-###'),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(10),
            'category_id' => Category::factory(),
            'supplier_id' => Supplier::factory(),
            'buy_price' => $buy,
            // A believable margin; round numbers keep the seeder output readable.
            'sell_price' => (int) round($buy * fake()->randomFloat(2, 1.2, 1.9) / 100) * 100,
            'min_stock' => fake()->numberBetween(5, 60),
            'current_stock' => fake()->numberBetween(0, 300),
            'unit' => fake()->randomElement(['piece', 'box', 'roll', 'pack', 'set', 'kg']),
        ];
    }

    /**
     * Out of stock, for exercising the red status badge.
     */
    public function outOfStock(): static
    {
        return $this->state(fn () => ['current_stock' => 0]);
    }

    /**
     * At or below the reorder point but not empty, for the amber badge.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_stock' => 10,
            'current_stock' => fake()->numberBetween(1, 10),
        ]);
    }

    /**
     * Comfortably above the reorder point, for the green badge.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_stock' => 20,
            'current_stock' => fake()->numberBetween(60, 400),
        ]);
    }
}