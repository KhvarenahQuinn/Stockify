<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            // Slugs are unique, so a plain fake() risks colliding as the table
            // grows. unique() plus a suffix keeps re-runs and large runs safe.
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(8),
        ];
    }
}