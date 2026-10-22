<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement([
                'product.created',
                'product.updated',
                'category.created',
                'supplier.updated',
                'stock.adjusted',
                'user.updated',
            ]),
            'description' => rtrim(fake()->sentence(7), '.'),
            'ip_address' => fake()->ipv4(),
        ];
    }

    /**
     * A system-initiated entry with no actor. user_id is nullable precisely for
     * this, and the activity report renders these as "system".
     */
    public function system(): static
    {
        return $this->state(fn () => ['user_id' => null]);
    }
}