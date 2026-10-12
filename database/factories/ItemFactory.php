<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => ItemCategory::factory(),

            'code' => fake()->unique()->numerify('ITM-####'),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),

            'unit' => fake()->randomElement(['PCS', 'BTG', 'KG', 'MTR', 'BOX']),
            'type' => Item::TYPE_STOCKABLE,

            'is_active' => true,
        ];
    }
}
