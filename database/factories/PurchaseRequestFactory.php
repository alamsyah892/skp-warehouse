<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Division;
use App\Models\Project;
use App\Models\PurchaseRequest;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'warehouse_id' => Warehouse::factory(),
            'warehouse_address_id' => WarehouseAddress::factory(),
            'division_id' => Division::factory(),
            'project_id' => Project::factory(),

            'description' => fake()->sentence(),
            'memo' => fake()->word(),
            'boq' => fake()->word(),
            'notes' => fake()->sentence(),

            'info' => fake()->sentence(),
        ];
    }
}
