<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\Division;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'company_id' => Company::factory(),
            'warehouse_id' => Warehouse::factory(),
            'warehouse_address_id' => WarehouseAddress::factory(),
            'division_id' => Division::factory(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'type' => PurchaseOrder::TYPE_PURCHASE_ORDER,
            'number' => $this->faker->unique()->bothify('PO/####/??'),
            'description' => $this->faker->sentence(),
            'memo' => $this->faker->word(),
            'termin' => $this->faker->word(),
            'notes' => $this->faker->paragraph(),
            'info' => $this->faker->sentence(),
            'discount' => 0,
            'tax' => 0,
            'tax_description' => '',
            'pembulatan' => 0,
            'status' => PurchaseOrderStatus::DRAFT,
        ];
    }
}
