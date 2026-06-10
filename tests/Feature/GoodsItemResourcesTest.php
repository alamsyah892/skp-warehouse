<?php

use App\Filament\Resources\GoodsIssueItems\GoodsIssueItemResource;
use App\Filament\Resources\GoodsIssueItems\Pages\ListGoodsIssueItems;
use App\Filament\Resources\GoodsReceiveItems\GoodsReceiveItemResource;
use App\Filament\Resources\GoodsReceiveItems\Pages\ListGoodsReceiveItems;
use App\Models\GoodsIssueItem;
use App\Models\GoodsReceiveItem;

test('goods receive item resource is registered as a list-only resource', function (): void {
    expect(GoodsReceiveItemResource::getModel())
        ->toBe(GoodsReceiveItem::class)
        ->and(GoodsReceiveItemResource::getModelLabel())
        ->toBe(__('goods-receive.goods_receive_items.label'))
        ->and(GoodsReceiveItemResource::getPages())
        ->toHaveKey('index')
        ->and(ListGoodsReceiveItems::getResource())
        ->toBe(GoodsReceiveItemResource::class);
});

test('goods issue item resource is registered as a list-only resource', function (): void {
    expect(GoodsIssueItemResource::getModel())
        ->toBe(GoodsIssueItem::class)
        ->and(GoodsIssueItemResource::getModelLabel())
        ->toBe(__('goods-issue.goods_issue_items.label'))
        ->and(GoodsIssueItemResource::getPages())
        ->toHaveKey('index')
        ->and(ListGoodsIssueItems::getResource())
        ->toBe(GoodsIssueItemResource::class);
});
