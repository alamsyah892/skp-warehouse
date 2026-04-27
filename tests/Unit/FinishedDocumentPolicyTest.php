<?php

use App\Enums\GoodsReceiveStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\GoodsReceive;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Policies\GoodsReceivePolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\PurchaseRequestPolicy;

it('denies updating finished purchase request in policy', function () {
    $user = mockUserCan('Update Purchase Request');

    $purchaseRequest = new PurchaseRequest();
    $purchaseRequest->status = PurchaseRequestStatus::FINISHED;

    expect(app(PurchaseRequestPolicy::class)->update($user, $purchaseRequest))->toBeFalse();
});

it('denies updating finished purchase order in policy', function () {
    $user = mockUserCan('Update Purchase Order');

    $purchaseOrder = new PurchaseOrder();
    $purchaseOrder->status = PurchaseOrderStatus::FINISHED;

    expect(app(PurchaseOrderPolicy::class)->update($user, $purchaseOrder))->toBeFalse();
});

it('denies updating goods receive when its purchase order is finished', function () {
    $user = mockUserCan('Update Goods Receipt');

    $purchaseOrder = new PurchaseOrder();
    $purchaseOrder->status = PurchaseOrderStatus::FINISHED;

    $goodsReceive = new GoodsReceive();
    $goodsReceive->status = GoodsReceiveStatus::RECEIVED;
    $goodsReceive->setRelation('purchaseOrder', $purchaseOrder);

    expect(app(GoodsReceivePolicy::class)->update($user, $goodsReceive))->toBeFalse();
});

function mockUserCan(string $permission): User
{
    $user = \Mockery::mock(User::class);
    $user->shouldReceive('can')
        ->with($permission)
        ->andReturn(true);

    return $user;
}
