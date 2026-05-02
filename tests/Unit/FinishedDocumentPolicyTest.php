<?php

use App\Enums\GoodsReceiveStatus;
use App\Enums\GoodsIssueStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequestStatus;
use App\Models\GoodsIssue;
use App\Models\GoodsReceive;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Policies\GoodsIssuePolicy;
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

it('denies updating confirmed goods receive in policy', function () {
    $user = mockUserCan('Update Goods Receipt');

    $goodsReceive = new GoodsReceive();
    $goodsReceive->status = GoodsReceiveStatus::CONFIRMED;

    expect(app(GoodsReceivePolicy::class)->update($user, $goodsReceive))->toBeFalse();
});

it('denies deleting confirmed goods receive in policy', function () {
    $user = mockUserCan('Delete Goods Receipt');

    $goodsReceive = new GoodsReceive();
    $goodsReceive->status = GoodsReceiveStatus::CONFIRMED;

    expect(app(GoodsReceivePolicy::class)->delete($user, $goodsReceive))->toBeFalse();
});

it('denies updating canceled goods issue in policy', function () {
    $user = mockUserCan('Update Goods Issue');

    $goodsIssue = new GoodsIssue();
    $goodsIssue->status = GoodsIssueStatus::CANCELED;

    expect(app(GoodsIssuePolicy::class)->update($user, $goodsIssue))->toBeFalse();
});

function mockUserCan(string $permission): User
{
    $user = \Mockery::mock(User::class);
    $user->shouldReceive('can')
        ->with($permission)
        ->andReturn(true);

    return $user;
}
