<?php

use App\Enums\GoodsReceiveStatus;
use App\Models\GoodsReceive;
use App\Models\Role;

it('allows confirming goods receive only for purchasing or finance roles', function () {
    $goodsReceive = new GoodsReceive();
    $goodsReceive->status = GoodsReceiveStatus::RECEIVED;
    $goodsReceive->user_id = 99;

    expect($goodsReceive->canChangeStatusTo(GoodsReceiveStatus::CONFIRMED, fakeUserWithRoles([Role::PURCHASING])))->toBeTrue()
        ->and($goodsReceive->canChangeStatusTo(GoodsReceiveStatus::CONFIRMED, fakeUserWithRoles([Role::FINANCE])))->toBeTrue()
        ->and($goodsReceive->canChangeStatusTo(GoodsReceiveStatus::CONFIRMED, fakeUserWithRoles([Role::LOGISTIC])))->toBeFalse()
        ->and($goodsReceive->canChangeStatusTo(GoodsReceiveStatus::CONFIRMED, fakeUserWithRoles([], 99)))->toBeFalse();
});

function fakeUserWithRoles(array $roles, int $id = 1): object
{
    return new class($roles, $id)
    {
        public function __construct(
            private array $roles,
            public int $id,
        ) {
        }

        public function hasAnyRole(array $roles): bool
        {
            return array_intersect($this->roles, $roles) !== [];
        }
    };
}
