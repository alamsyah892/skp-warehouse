<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Bank;
use App\Models\CashBankTransaction;
use App\Models\Company;
use App\Models\Project;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashBankTransaction>
 */
class CashBankTransactionFactory extends Factory
{
    protected $model = CashBankTransaction::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(TransactionType::cases()),
            'company_id' => Company::factory(),
            'bank_id' => Bank::factory(),
            'vendor_id' => null,
            'project_id' => null,
            'number' => fake()->unique()->numerify('CBT-2026-######'),
            'date' => fake()->date(),
            'amount' => fake()->randomFloat(2, 1000, 10000000),
            'check_number' => '',
            'description' => fake()->sentence(),
            'notes' => '',
            'info' => '',
        ];
    }

    public function receipt(): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionType::Receipt,
        ]);
    }

    public function payment(): static
    {
        return $this->state(fn (): array => [
            'type' => TransactionType::Payment,
        ]);
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
            'bank_id' => Bank::factory()->state(['company_id' => $company->id]),
        ]);
    }

    public function withProject(Project $project): static
    {
        return $this->state(fn (): array => [
            'project_id' => $project->id,
        ]);
    }

    public function withVendor(Vendor $vendor): static
    {
        return $this->state(fn (): array => [
            'vendor_id' => $vendor->id,
        ]);
    }
}
