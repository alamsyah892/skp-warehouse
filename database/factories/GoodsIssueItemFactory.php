<?php

namespace Database\Factories;

use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsIssueItem>
 */
class GoodsIssueItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'goods_issue_id' => GoodsIssue::factory(),
            'item_id' => Item::factory(),
            'qty' => fake()->randomFloat(2, 1, 20),
            'description' => fake()->sentence(),
            'sort' => 1,
        ];
    }
}
