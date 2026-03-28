<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrderItem>
 */
class PurchaseOrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'purchase_request_item_id' => PurchaseRequestItem::factory(),
            'item_id' => Item::factory(),
            'qty' => $this->faker->randomFloat(2, 1, 100),
            'price' => $this->faker->randomFloat(2, 1000, 100000),
            'discount' => $this->faker->randomFloat(2, 0, 1000),
            'description' => $this->faker->sentence(),
            'sort' => 0,
        ];
    }
}
