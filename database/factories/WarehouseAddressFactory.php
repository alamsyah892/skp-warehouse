<?php

namespace Database\Factories;

use App\Models\Warehouse;
use App\Models\WarehouseAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseAddress>
 */
class WarehouseAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),

            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'post_code' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
            'fax' => fake()->phoneNumber(),

            'as_default' => false,
        ];
    }
}
