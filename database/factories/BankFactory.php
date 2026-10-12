<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bank>
 */
class BankFactory extends Factory
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
            'currency_id' => Currency::factory(),

            'code' => fake()->unique()->numerify('BNK-####'),
            'name' => fake()->company(),
            'description' => fake()->sentence(),

            'account_number' => fake()->bankAccountNumber(),
            'balance' => 0,

            'is_active' => true,
        ];
    }
}
