<?php

namespace Database\Factories;

use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsReceiveType;
use App\Models\Company;
use App\Models\Division;
use App\Models\GoodsReceive;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceive>
 */
class GoodsReceiveFactory extends Factory
{
    protected $model = GoodsReceive::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'company_id' => Company::factory(),
            'warehouse_id' => Warehouse::factory(),
            'warehouse_address_id' => WarehouseAddress::factory(),
            'division_id' => Division::factory(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'type' => GoodsReceiveType::PURCHASE_ORDER,
            'status' => GoodsReceiveStatus::RECEIVED,
            'number' => fake()->unique()->bothify('GR/####/??'),
            'description' => fake()->sentence(),
            'delivery_order' => fake()->bothify('DO-####'),
            'notes' => '',
            'info' => '',
        ];
    }
}
