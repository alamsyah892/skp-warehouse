<?php

use App\Enums\TransactionType;
use App\Filament\Resources\CashBankTransactions\CashBankTransactionResource;
use App\Models\Bank;
use App\Models\CashBankTransaction;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Project;
use App\Rules\BelongsToCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

function createCompanyForCashBankTest(): Company
{
    return Company::query()->create([
        'code' => 'CMP01',
        'name' => 'Test Company',
        'description' => '',
        'alias' => 'TC',
        'address' => 'Address',
        'city' => 'Jakarta',
        'post_code' => '12345',
        'contact_person' => 'John',
        'contact_person_position' => 'Manager',
        'phone' => '08123456789',
        'fax' => '',
        'email' => 'company@example.com',
        'website' => '',
        'tax_number' => '123',
        'is_active' => true,
    ]);
}

function createCurrencyForCashBankTest(): Currency
{
    return Currency::query()->create([
        'code' => 'IDR',
        'name' => 'Rupiah',
        'is_active' => true,
    ]);
}

function createBankForCashBankTest(Company $company, Currency $currency): Bank
{
    return Bank::query()->create([
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'code' => 'BCA01',
        'name' => 'BCA Main',
        'description' => '',
        'account_number' => '1234567890',
        'balance' => 0,
        'is_active' => true,
    ]);
}

function createProjectForCashBankTest(): Project
{
    return Project::query()->create([
        'code' => 'PRJ01',
        'po_code' => 'PO01',
        'name' => 'Test Project',
        'description' => '',
        'allow_po' => true,
        'is_active' => true,
    ]);
}

test('cash bank transaction resource is configured correctly', function (): void {
    expect(CashBankTransactionResource::getModel())
        ->toBe(CashBankTransaction::class)
        ->and(CashBankTransactionResource::getNavigationGroup())
        ->toBe('Kas & Bank')
        ->and(CashBankTransactionResource::getNavigationLabel())
        ->toBe('Transaksi Kas/Bank')
        ->and(CashBankTransactionResource::getGloballySearchableAttributes())
        ->toBe(['number', 'description']);
});

test('transaction type enum has expected labels and colors', function (): void {
    expect(TransactionType::Receipt->label())->toBe('Penerimaan')
        ->and(TransactionType::Receipt->color())->toBe('success')
        ->and(TransactionType::Payment->label())->toBe('Pengeluaran')
        ->and(TransactionType::Payment->color())->toBe('danger')
        ->and(TransactionType::options())->toBe([
            '1' => 'Penerimaan',
            '2' => 'Pengeluaran',
        ]);
});

test('cash bank transaction number is generated in CBT format', function (): void {
    $company = createCompanyForCashBankTest();
    $currency = createCurrencyForCashBankTest();
    $bank = createBankForCashBankTest($company, $currency);

    $first = CashBankTransaction::query()->create([
        'type' => TransactionType::Receipt,
        'company_id' => $company->id,
        'bank_id' => $bank->id,
        'date' => '2026-06-10',
        'amount' => 100000,
    ]);

    $second = CashBankTransaction::query()->create([
        'type' => TransactionType::Payment,
        'company_id' => $company->id,
        'bank_id' => $bank->id,
        'date' => '2026-06-10',
        'amount' => 50000,
    ]);

    expect($first->number)->toMatch('/^CBT-2026-\d{6}$/')
        ->and($second->number)->toMatch('/^CBT-2026-\d{6}$/')
        ->and($first->number)->not->toBe($second->number);
});

test('belongs to company rule validates bank and project ownership', function (): void {
    $companyA = createCompanyForCashBankTest();
    $companyB = Company::query()->create([
        'code' => 'CMP02',
        'name' => 'Other Company',
        'description' => '',
        'alias' => 'OC',
        'address' => 'Address',
        'city' => 'Bandung',
        'post_code' => '54321',
        'contact_person' => 'Jane',
        'contact_person_position' => 'Manager',
        'phone' => '08111111111',
        'fax' => '',
        'email' => 'other@example.com',
        'website' => '',
        'tax_number' => '456',
        'is_active' => true,
    ]);

    $currency = createCurrencyForCashBankTest();
    $bankForCompanyB = createBankForCashBankTest($companyB, $currency);

    $project = createProjectForCashBankTest();
    $project->companies()->attach($companyA->id);

    $bankFails = Validator::make(
        ['bank_id' => $bankForCompanyB->id],
        ['bank_id' => [new BelongsToCompany($companyA->id, Bank::class)]],
    )->fails();

    $projectPasses = Validator::make(
        ['project_id' => $project->id],
        ['project_id' => [new BelongsToCompany($companyA->id, Project::class, 'many')]],
    )->passes();

    $projectFails = Validator::make(
        ['project_id' => $project->id],
        ['project_id' => [new BelongsToCompany($companyB->id, Project::class, 'many')]],
    )->fails();

    expect($bankFails)->toBeTrue()
        ->and($projectPasses)->toBeTrue()
        ->and($projectFails)->toBeTrue();
});

test('cash bank transaction stores relationships and casts', function (): void {
    $company = createCompanyForCashBankTest();
    $currency = createCurrencyForCashBankTest();
    $bank = createBankForCashBankTest($company, $currency);
    $project = createProjectForCashBankTest();
    $project->companies()->attach($company->id);

    $transaction = CashBankTransaction::query()->create([
        'type' => TransactionType::Receipt,
        'company_id' => $company->id,
        'bank_id' => $bank->id,
        'project_id' => $project->id,
        'date' => '2026-06-10',
        'amount' => 1500000.50,
        'description' => 'Penerimaan kas',
    ]);

    $transaction->refresh();

    expect($transaction->type)->toBe(TransactionType::Receipt)
        ->and($transaction->company->is($company))->toBeTrue()
        ->and($transaction->bank->is($bank))->toBeTrue()
        ->and($transaction->project->is($project))->toBeTrue()
        ->and($transaction->amount)->toBe('1500000.50');
});
