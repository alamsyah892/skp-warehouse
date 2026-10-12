<?php

namespace Database\Factories;

use App\Models\ItemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemCategory>
 */
class ItemCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'level' => ItemCategory::LEVEL_CATEGORY,

            'code' => fake()->unique()->numerify('CAT-####'),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),

            'allow_po' => true,
        ];
    }
}
