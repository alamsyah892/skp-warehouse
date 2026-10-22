<?php

use App\Filament\Resources\GoodsReceives\Pages\ViewGoodsReceive;
use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

test('goods receive is downloaded as goods receive pdf', function (): void {
    $user = createUserWithReadGoodsReceivePermission();
    $this->actingAs($user);

    $goodsReceive = GoodsReceive::factory()->create();
    $cleanNumber = '099/10/26/GR/TEST-'.fake()->unique()->numberBetween(1000, 9999);
    $goodsReceive->update([
        'number' => $cleanNumber,
    ]);
    GoodsReceiveItem::factory()
        ->for($goodsReceive)
        ->create();

    $expectedFilename = 'GR_'.str_replace('/', '_', $cleanNumber).'.pdf';

    $response = $this->get(route('goods-receives.print', $goodsReceive));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload($expectedFilename);
});

test('goods receive can be streamed inline', function (): void {
    $user = createUserWithReadGoodsReceivePermission();
    $this->actingAs($user);

    $goodsReceive = GoodsReceive::factory()->create();
    GoodsReceiveItem::factory()
        ->for($goodsReceive)
        ->create();

    $response = $this->get(route('goods-receives.print', [
        'goodsReceive' => $goodsReceive,
        'inline' => 1,
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('user without read goods receipt permission is forbidden', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $goodsReceive = GoodsReceive::factory()->create();

    $this->get(route('goods-receives.print', $goodsReceive))
        ->assertForbidden();
});

test('guest is redirected to login', function (): void {
    $goodsReceive = GoodsReceive::factory()->create();

    $this->get(route('goods-receives.print', $goodsReceive))
        ->assertRedirectToRoute('filament.admin.auth.login');
});

test('view goods receive page renders print action for authorized user', function (): void {
    $user = createUserWithReadGoodsReceivePermission();
    $this->actingAs($user);

    $goodsReceive = GoodsReceive::factory()->create();

    Livewire::test(ViewGoodsReceive::class, [
        'record' => $goodsReceive->getRouteKey(),
    ])
        ->assertActionExists('print')
        ->assertActionVisible('print');
});

test('single page goods receive has signature at bottom of page 1', function (): void {
    $user = createUserWithReadGoodsReceivePermission();
    $this->actingAs($user);

    $goodsReceive = GoodsReceive::factory()->create();
    GoodsReceiveItem::factory()
        ->count(5)
        ->for($goodsReceive)
        ->create(['description' => null]);

    $response = $this->get(route('goods-receives.print', [
        'goodsReceive' => $goodsReceive,
        'inline' => 1,
    ]));

    $response->assertOk();
    $content = $response->getContent();
    $pageCount = preg_match_all('~/Type\s*/Page\b~', $content);
    expect($pageCount)->toBe(1);

    if (preg_match_all('~stream[\r\n]+(.*?)[\r\n]+endstream~s', $content, $matches)) {
        $stream = @gzuncompress($matches[1][0]) ?: $matches[1][0];
        expect(preg_match('~BT\s+[0-9\.\-]+\s+([0-9\.\-]+)\s+Td\s+/F2\s+10\.0\s+Tf\s+\[\((Pengirim / Ekspedisi,)\)\]~', $stream, $m))->toBe(1);
        $yCoord = (float) $m[1];
        expect($yCoord)->toBeGreaterThan(100.0)->toBeLessThan(250.0);
    }
});

test('multi page goods receive does not cut off items on page 1 and places signature at bottom of last page', function (): void {
    $user = createUserWithReadGoodsReceivePermission();
    $this->actingAs($user);

    $goodsReceive = GoodsReceive::factory()->create();
    for ($i = 1; $i <= 40; $i++) {
        GoodsReceiveItem::factory()
            ->for($goodsReceive)
            ->create(['description' => null]);
    }

    $response = $this->get(route('goods-receives.print', [
        'goodsReceive' => $goodsReceive,
        'inline' => 1,
    ]));

    $response->assertOk();
    $content = $response->getContent();
    $pageCount = preg_match_all('~/Type\s*/Page\b~', $content);
    expect($pageCount)->toBeGreaterThan(1);

    if (preg_match_all('~stream[\r\n]+(.*?)[\r\n]+endstream~s', $content, $matches)) {
        $page1Stream = @gzuncompress($matches[1][0]) ?: $matches[1][0];
        preg_match_all('~Td\s+/F2\s+10\.0\s+Tf\s+\[\(([0-9]+)\)\]~', $page1Stream, $p1Items);
        expect(count($p1Items[1]))->toBeGreaterThan(15);

        $lastStream = @gzuncompress(end($matches[1])) ?: end($matches[1]);
        expect(preg_match('~BT\s+[0-9\.\-]+\s+([0-9\.\-]+)\s+Td\s+/F2\s+10\.0\s+Tf\s+\[\((Pengirim / Ekspedisi,)\)\]~', $lastStream, $m))->toBe(1);
        $yCoord = (float) $m[1];
        expect($yCoord)->toBeGreaterThan(100.0)->toBeLessThan(250.0);
    }
});
