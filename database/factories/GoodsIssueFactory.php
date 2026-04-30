<?php

namespace Database\Factories;

use App\Enums\GoodsIssueType;
use App\Models\Company;
use App\Models\Division;
use App\Models\GoodsIssue;
use App\Models\Project;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsIssue>
 */
class GoodsIssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'warehouse_id' => Warehouse::factory(),
            'warehouse_address_id' => WarehouseAddress::factory(),
            'division_id' => Division::factory(),
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'type' => GoodsIssueType::ISSUE,
            'number' => fake()->unique()->numerify('GI/##/##/PRJ/DIV/###'),
            'description' => fake()->sentence(),
            'notes' => '',
            'info' => '',
        ];
    }
}
