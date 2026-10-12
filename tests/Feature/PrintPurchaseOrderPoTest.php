<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ordered purchase order is downloaded as purchase order pdf', function (): void {
    $user = createUserWithReadPurchaseOrderPermission();
    $this->actingAs($user);

    $purchaseOrder = PurchaseOrder::factory()->create();
    $purchaseOrder->update([
        'number' => '043/10/26/PO-M/RSKP-BU',
        'status' => PurchaseOrderStatus::ORDERED,
    ]);
    PurchaseOrderItem::factory()
        ->for($purchaseOrder)
        ->create(['purchase_request_item_id' => null]);

    $response = $this->get(route('purchase-orders.print-po', $purchaseOrder));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('PO_043_10_26_PO-M_RSKP-BU.pdf');
});

test('non ordered purchase order is not downloadable', function (): void {
    $user = createUserWithReadPurchaseOrderPermission();
    $this->actingAs($user);

    $purchaseOrder = PurchaseOrder::factory()->create();

    $this->get(route('purchase-orders.print-po', $purchaseOrder))
        ->assertNotFound();
});

test('ordered purchase order without read permission is forbidden', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchaseOrder = PurchaseOrder::factory()->create();
    $purchaseOrder->update(['status' => PurchaseOrderStatus::ORDERED]);

    $this->get(route('purchase-orders.print-po', $purchaseOrder))
        ->assertForbidden();
});

test('guest is redirected to login', function (): void {
    $this->actingAs(User::factory()->create());

    $purchaseOrder = PurchaseOrder::factory()->create();
    $purchaseOrder->update(['status' => PurchaseOrderStatus::ORDERED]);

    $this->app['auth']->forgetGuards();

    $this->get(route('purchase-orders.print-po', $purchaseOrder))
        ->assertRedirectToRoute('filament.admin.auth.login');
});
