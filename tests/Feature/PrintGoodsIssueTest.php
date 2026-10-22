<?php

use App\Filament\Resources\GoodsIssues\Pages\ViewGoodsIssue;
use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

test('goods issue is downloaded as goods issue pdf', function (): void {
    $user = createUserWithReadGoodsIssuePermission();
    $this->actingAs($user);

    $goodsIssue = GoodsIssue::factory()->create();
    $cleanNumber = '099/10/26/GI/TEST-'.fake()->unique()->numberBetween(1000, 9999);
    $goodsIssue->update([
        'number' => $cleanNumber,
    ]);
    GoodsIssueItem::factory()
        ->for($goodsIssue)
        ->create();

    $expectedFilename = 'GI_'.str_replace('/', '_', $cleanNumber).'.pdf';

    $response = $this->get(route('goods-issues.print', $goodsIssue));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload($expectedFilename);
});

test('goods issue can be streamed inline', function (): void {
    $user = createUserWithReadGoodsIssuePermission();
    $this->actingAs($user);

    $goodsIssue = GoodsIssue::factory()->create();
    GoodsIssueItem::factory()
        ->for($goodsIssue)
        ->create();

    $response = $this->get(route('goods-issues.print', [
        'goodsIssue' => $goodsIssue,
        'inline' => 1,
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('user without read goods issue permission is forbidden', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $goodsIssue = GoodsIssue::factory()->create();

    $this->get(route('goods-issues.print', $goodsIssue))
        ->assertForbidden();
});

test('guest is redirected to login', function (): void {
    $goodsIssue = GoodsIssue::factory()->create();

    $this->get(route('goods-issues.print', $goodsIssue))
        ->assertRedirectToRoute('filament.admin.auth.login');
});

test('view goods issue page renders print action for authorized user', function (): void {
    $user = createUserWithReadGoodsIssuePermission();
    $this->actingAs($user);

    $goodsIssue = GoodsIssue::factory()->create();

    Livewire::test(ViewGoodsIssue::class, [
        'record' => $goodsIssue->getRouteKey(),
    ])
        ->assertActionExists('print')
        ->assertActionVisible('print');
});

test('single page goods issue has signature at bottom of page 1', function (): void {
    $user = createUserWithReadGoodsIssuePermission();
    $this->actingAs($user);

    $goodsIssue = GoodsIssue::factory()->create();
    GoodsIssueItem::factory()
        ->count(5)
        ->for($goodsIssue)
        ->create(['description' => null]);

    $response = $this->get(route('goods-issues.print', [
        'goodsIssue' => $goodsIssue,
        'inline' => 1,
    ]));

    $response->assertOk();
    $content = $response->getContent();
    $pageCount = preg_match_all('~/Type\s*/Page\b~', $content);
    expect($pageCount)->toBe(1);

    if (preg_match_all('~stream[\r\n]+(.*?)[\r\n]+endstream~s', $content, $matches)) {
        $stream = @gzuncompress($matches[1][0]) ?: $matches[1][0];
        expect(preg_match('~BT\s+[0-9\.\-]+\s+([0-9\.\-]+)\s+Td\s+/F2\s+10\.0\s+Tf\s+\[\((Yang Meminta / Penerima,)\)\]~', $stream, $m))->toBe(1);
        $yCoord = (float) $m[1];
        expect($yCoord)->toBeGreaterThan(100.0)->toBeLessThan(250.0);
    }
});

test('multi page goods issue does not cut off items on page 1 and places signature at bottom of last page', function (): void {
    $user = createUserWithReadGoodsIssuePermission();
    $this->actingAs($user);

    $goodsIssue = GoodsIssue::factory()->create();
    for ($i = 1; $i <= 40; $i++) {
        GoodsIssueItem::factory()
            ->for($goodsIssue)
            ->create(['description' => null]);
    }

    $response = $this->get(route('goods-issues.print', [
        'goodsIssue' => $goodsIssue,
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
        expect(preg_match('~BT\s+[0-9\.\-]+\s+([0-9\.\-]+)\s+Td\s+/F2\s+10\.0\s+Tf\s+\[\((Yang Meminta / Penerima,)\)\]~', $lastStream, $m))->toBe(1);
        $yCoord = (float) $m[1];
        expect($yCoord)->toBeGreaterThan(100.0)->toBeLessThan(250.0);
    }
});
