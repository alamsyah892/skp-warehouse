<?php

use App\Enums\GoodsReceiveStatus;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\GoodsReceives\Schemas\GoodsReceiveInfolist;
use App\Models\GoodsReceive;
use App\Models\PurchaseOrder;

it('hides returned and canceled actions when purchase order is finished', function () {
    $purchaseOrder = new PurchaseOrder();
    $purchaseOrder->status = PurchaseOrderStatus::FINISHED;

    $goodsReceive = new GoodsReceive();
    $goodsReceive->status = GoodsReceiveStatus::RECEIVED;
    $goodsReceive->setRelation('purchaseOrder', $purchaseOrder);

    $method = new \ReflectionMethod(GoodsReceiveInfolist::class, 'shouldHideStatusAction');
    $method->setAccessible(true);

    expect($method->invoke(null, $goodsReceive, GoodsReceiveStatus::RETURNED))->toBeTrue()
        ->and($method->invoke(null, $goodsReceive, GoodsReceiveStatus::CANCELED))->toBeTrue()
        ->and($method->invoke(null, $goodsReceive, GoodsReceiveStatus::RECEIVED))->toBeFalse();
});
