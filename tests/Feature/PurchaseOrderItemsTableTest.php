<?php

use App\Livewire\PurchaseOrderItemsTable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('price column is visible only to allowed roles', function (string $roleName, bool $canViewPriceColumn) {
    Role::create(['name' => $roleName]);

    $user = User::factory()->create();
    $user->assignRole($roleName);

    expect(PurchaseOrderItemsTable::canViewPriceColumn($user))->toBe($canViewPriceColumn);
})->with([
    'purchasing' => [Role::PURCHASING, true],
    'finance' => [Role::FINANCE, true],
    'audit' => [Role::AUDIT, true],
    'project owner' => [Role::PROJECT_OWNER, true],
    'administrator' => [Role::ADMINISTRATOR, true],
    'logistic' => [Role::LOGISTIC, false],
    'purchasing manager' => [Role::PURCHASING_MANAGER, false],
    'finance manager' => [Role::FINANCE_MANAGER, false],
    'audit manager' => [Role::AUDIT_MANAGER, false],
]);
