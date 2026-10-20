<?php

use App\Filament\Resources\PurchaseRequests\Pages\ViewPurchaseRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('purchase request is downloaded as purchase request pdf', function (): void {
    $user = createUserWithReadPurchaseRequestPermission();
    $this->actingAs($user);

    $purchaseRequest = PurchaseRequest::factory()->create();
    $purchaseRequest->update([
        'number' => '001/10/26/PR/RSKP-BU',
    ]);
    PurchaseRequestItem::factory()
        ->for($purchaseRequest)
        ->create();

    $response = $this->get(route('purchase-requests.print', $purchaseRequest));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('PR_001_10_26_PR_RSKP-BU.pdf');
});

test('purchase request can be streamed inline', function (): void {
    $user = createUserWithReadPurchaseRequestPermission();
    $this->actingAs($user);

    $purchaseRequest = PurchaseRequest::factory()->create();
    PurchaseRequestItem::factory()
        ->for($purchaseRequest)
        ->create();

    $response = $this->get(route('purchase-requests.print', [
        'purchaseRequest' => $purchaseRequest,
        'inline' => 1,
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('user without read purchase request permission is forbidden', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchaseRequest = PurchaseRequest::factory()->create();

    $this->get(route('purchase-requests.print', $purchaseRequest))
        ->assertForbidden();
});

test('guest is redirected to login', function (): void {
    $this->actingAs(User::factory()->create());

    $purchaseRequest = PurchaseRequest::factory()->create();

    $this->app['auth']->forgetGuards();

    $this->get(route('purchase-requests.print', $purchaseRequest))
        ->assertRedirectToRoute('filament.admin.auth.login');
});

test('view purchase request page renders print action for authorized user', function (): void {
    $user = createUserWithReadPurchaseRequestPermission();
    $this->actingAs($user);

    $purchaseRequest = PurchaseRequest::factory()->create();

    Livewire::test(ViewPurchaseRequest::class, [
        'record' => $purchaseRequest->getRouteKey(),
    ])
        ->assertActionExists('print')
        ->assertActionVisible('print');
});
