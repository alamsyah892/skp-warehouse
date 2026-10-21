<?php

use App\Filament\Resources\PurchaseRequests\Pages\ViewPurchaseRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

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

test('single page purchase request has signature at bottom of page 1', function (): void {
    $user = createUserWithReadPurchaseRequestPermission();
    $this->actingAs($user);

    $purchaseRequest = PurchaseRequest::factory()->create();
    PurchaseRequestItem::factory()
        ->count(5)
        ->for($purchaseRequest)
        ->create(['description' => null]);

    $response = $this->get(route('purchase-requests.print', [
        'purchaseRequest' => $purchaseRequest,
        'inline' => 1,
    ]));

    $response->assertOk();
    $content = $response->getContent();
    $pageCount = preg_match_all('~/Type\s*/Page\b~', $content);
    expect($pageCount)->toBe(1);

    if (preg_match_all('~stream[\r\n]+(.*?)[\r\n]+endstream~s', $content, $matches)) {
        $stream = @gzuncompress($matches[1][0]) ?: $matches[1][0];
        expect(preg_match('~BT\s+[0-9\.\-]+\s+([0-9\.\-]+)\s+Td\s+/F2\s+10\.0\s+Tf\s+\[\((Pemesan,)\)\]~', $stream, $m))->toBe(1);
        $yCoord = (float) $m[1];
        expect($yCoord)->toBeGreaterThan(200.0)->toBeLessThan(300.0);
    }
});

test('multi page purchase request does not cut off items on page 1 and places signature at bottom of last page', function (): void {
    $user = createUserWithReadPurchaseRequestPermission();
    $this->actingAs($user);

    $purchaseRequest = PurchaseRequest::factory()->create();
    for ($i = 1; $i <= 25; $i++) {
        PurchaseRequestItem::factory()
            ->for($purchaseRequest)
            ->create(['description' => null]);
    }

    $response = $this->get(route('purchase-requests.print', [
        'purchaseRequest' => $purchaseRequest,
        'inline' => 1,
    ]));

    $response->assertOk();
    $content = $response->getContent();
    $pageCount = preg_match_all('~/Type\s*/Page\b~', $content);
    expect($pageCount)->toBeGreaterThan(1);

    if (preg_match_all('~stream[\r\n]+(.*?)[\r\n]+endstream~s', $content, $matches)) {
        // Page 1 should contain items and not be cut off
        $page1Stream = @gzuncompress($matches[1][0]) ?: $matches[1][0];
        preg_match_all('~Td\s+/F2\s+10\.0\s+Tf\s+\[\(([0-9]+)\)\]~', $page1Stream, $p1Items);
        expect(count($p1Items[1]))->toBeGreaterThan(15);

        // Last page should contain signature positioned at the bottom
        $lastStream = @gzuncompress(end($matches[1])) ?: end($matches[1]);
        expect(preg_match('~BT\s+[0-9\.\-]+\s+([0-9\.\-]+)\s+Td\s+/F2\s+10\.0\s+Tf\s+\[\((Pemesan,)\)\]~', $lastStream, $m))->toBe(1);
        $yCoord = (float) $m[1];
        expect($yCoord)->toBeGreaterThan(200.0)->toBeLessThan(300.0);
    }
});
