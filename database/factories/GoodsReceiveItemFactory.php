<?php

namespace Database\Factories;

use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use App\Models\Item;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceiveItem>
 */
class GoodsReceiveItemFactory extends Factory
{
    protected $model = GoodsReceiveItem::class;

    public function definition(): array
    {
        return [
            'goods_receive_id' => GoodsReceive::factory(),
            'purchase_order_item_id' => PurchaseOrderItem::factory(),
            'item_id' => Item::factory(),
            'qty' => fake()->randomFloat(2, 1, 100),
            'description' => fake()->sentence(),
            'sort' => 0,
        ];
    }
}
