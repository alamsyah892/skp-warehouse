<?php

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Enums\PurchaseOrderStatus;

it('can create a purchase order with items', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchaseOrder = PurchaseOrder::factory()
        ->has(PurchaseOrderItem::factory()->count(3), 'purchaseOrderItems')
        ->create();

    expect($purchaseOrder->purchaseOrderItems)->toHaveCount(3);
    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::DRAFT);
});

it('can generate a document number on creation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchaseOrder = PurchaseOrder::factory()->create();

    expect($purchaseOrder->number)->not->toBeEmpty();
});
