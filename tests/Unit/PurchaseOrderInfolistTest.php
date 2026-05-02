<?php

use App\Enums\GoodsReceiveStatus;
use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderInfolist;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('hides finish action when purchase order has no goods receives', function () {
    $record = mockPurchaseOrderForFinishAction(false);

    expect(invokePurchaseOrderStatusVisibility($record, PurchaseOrderStatus::FINISHED))->toBeTrue();
});

it('hides finish action when purchase order has unconfirmed goods receives', function () {
    $record = mockPurchaseOrderForFinishAction(true, true);

    expect(invokePurchaseOrderStatusVisibility($record, PurchaseOrderStatus::FINISHED))->toBeTrue();
});

it('shows finish action when all purchase order goods receives are confirmed', function () {
    $record = mockPurchaseOrderForFinishAction(true, false);

    expect(invokePurchaseOrderStatusVisibility($record, PurchaseOrderStatus::FINISHED))->toBeFalse();
});

function invokePurchaseOrderStatusVisibility(PurchaseOrder $record, PurchaseOrderStatus $status): bool
{
    $method = new ReflectionMethod(PurchaseOrderInfolist::class, 'shouldHideStatusAction');
    $method->setAccessible(true);

    return $method->invoke(null, $record, $status);
}

function mockPurchaseOrderForFinishAction(bool $hasGoodsReceives, ?bool $hasUnconfirmedGoodsReceives = null): PurchaseOrder
{
    $relation = \Mockery::mock(HasMany::class);

    $relation->shouldReceive('exists')
        ->once()
        ->andReturn($hasGoodsReceives);

    if ($hasGoodsReceives) {
        $relation->shouldReceive('where')
            ->once()
            ->with('status', '!=', GoodsReceiveStatus::CONFIRMED)
            ->andReturnSelf();

        $relation->shouldReceive('exists')
            ->once()
            ->andReturn($hasUnconfirmedGoodsReceives);
    }

    $record = \Mockery::mock(PurchaseOrder::class)->makePartial();
    $record->shouldReceive('goodsReceives')
        ->times($hasGoodsReceives ? 2 : 1)
        ->andReturn($relation);

    return $record;
}
