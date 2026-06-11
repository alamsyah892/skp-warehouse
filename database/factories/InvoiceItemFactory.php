<?php

namespace Database\Factories;

use App\Models\GoodsReceiveItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $qty = fake()->randomFloat(2, 1, 100);
        $price = fake()->randomFloat(2, 1000, 100000);

        return [
            'invoice_id' => Invoice::factory(),
            'goods_receive_item_id' => GoodsReceiveItem::factory(),
            'purchase_order_item_id' => PurchaseOrderItem::factory(),
            'item_id' => Item::factory(),
            'qty' => $qty,
            'price' => $price,
            'subtotal' => $qty * $price,
            'description' => fake()->sentence(),
            'sort' => 0,
        ];
    }
}
