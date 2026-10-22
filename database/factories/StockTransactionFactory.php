<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransaction>
 */
class StockTransactionFactory extends Factory
{
    protected $model = StockTransaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['IN', 'OUT']);
        $quantity = fake()->numberBetween(5, 120);
        $before = fake()->numberBetween(0, 400);
        $after = $type === 'IN' ? $before + $quantity : max(0, $before - $quantity);

        return [
            // reference_number is unique, so it has to be unique per record.
            'reference_number' => fake()->unique()->bothify('TRX-#####'),
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'approved_by' => null,
            'type' => $type,
            'quantity' => $quantity,
            'stock_before' => $before,
            'stock_after' => $after,
            'status' => 'APPROVED',
            'notes' => fake()->sentence(6),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'PENDING', 'approved_by' => null]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => 'REJECTED']);
    }

    public function approvedBy(User $user): static
    {
        return $this->state(fn () => ['status' => 'APPROVED', 'approved_by' => $user->id]);
    }
}