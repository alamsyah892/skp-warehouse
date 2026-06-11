<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderTaxType;
use App\Models\GoodsReceive;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'goods_receive_id' => GoodsReceive::factory(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'user_id' => User::factory(),
            'number' => fake()->unique()->numerify('INV-2026-######'),
            'invoice_date' => fake()->date(),
            'invoice_due_date' => fake()->dateTimeBetween('+1 day', '+45 days')->format('Y-m-d'),
            'discount' => 0,
            'tax_type' => PurchaseOrderTaxType::EXCLUDE,
            'tax_percentage' => 0,
            'rounding' => 0,
            'total_amount' => 0,
            'notes' => '',
            'info' => '',
        ];
    }
}
