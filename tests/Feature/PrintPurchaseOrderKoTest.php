<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseOrderType;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

test('draft purchase order is downloaded as confirmation order pdf', function (): void {
    $user = createUserWithReadPurchaseOrderPermission();
    $this->actingAs($user);

    $purchaseOrder = PurchaseOrder::factory()->create();
    $purchaseOrder->update(['number' => '004/10/26/PO-M/CMR-BXPCG']);
    PurchaseOrderItem::factory()
        ->for($purchaseOrder)
        ->create(['purchase_request_item_id' => null]);

    $response = $this->get(route('purchase-orders.print-ko', $purchaseOrder));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('KO_004_10_26_PO-M_CMR-BXPCG.pdf');
});

test('draft white purchase order is downloaded as white confirmation order pdf', function (): void {
    $user = createUserWithReadPurchaseOrderPermission();
    $this->actingAs($user);

    $purchaseOrder = PurchaseOrder::factory()->create([
        'type' => PurchaseOrderType::WHITE,
    ]);
    $purchaseOrder->update(['number' => '005/10/26/PO-P/CMR-BXPCG']);
    PurchaseOrderItem::factory()
        ->for($purchaseOrder)
        ->create(['purchase_request_item_id' => null]);

    $response = $this->get(route('purchase-orders.print-ko', $purchaseOrder));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('KO_005_10_26_PO-P_CMR-BXPCG.pdf');
});

test('non draft purchase order is not downloadable', function (): void {
    $user = createUserWithReadPurchaseOrderPermission();
    $this->actingAs($user);

    $purchaseOrder = PurchaseOrder::factory()->create();
    $purchaseOrder->update(['status' => PurchaseOrderStatus::ORDERED]);

    $this->get(route('purchase-orders.print-ko', $purchaseOrder))
        ->assertNotFound();
});

test('user without read purchase order permission is forbidden', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchaseOrder = PurchaseOrder::factory()->create();

    $this->get(route('purchase-orders.print-ko', $purchaseOrder))
        ->assertForbidden();
});

test('guest is redirected to login', function (): void {
    $this->actingAs(User::factory()->create());

    $purchaseOrder = PurchaseOrder::factory()->create();

    $this->app['auth']->forgetGuards();

    $this->get(route('purchase-orders.print-ko', $purchaseOrder))
        ->assertRedirectToRoute('filament.admin.auth.login');
});
