<?php

use App\Filament\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

it('filters out purchase order items that belong to deselected purchase requests', function () {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');

    DB::purge('sqlite');
    DB::reconnect('sqlite');

    Schema::create('purchase_request_items', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('purchase_request_id');
    });

    DB::table('purchase_request_items')->insert([
        [
            'id' => 101,
            'purchase_request_id' => 11,
        ],
        [
            'id' => 202,
            'purchase_request_id' => 22,
        ],
    ]);

    $filteredItems = PurchaseOrderForm::filterPurchaseOrderItemsForSelection(
        items: [
            [
                'purchase_request_item_id' => 101,
                'description' => 'Keep sourced item',
            ],
            [
                'purchase_request_item_id' => 202,
                'description' => 'Remove sourced item',
            ],
            [
                'purchase_request_item_id' => null,
                'description' => 'Keep manual item',
            ],
        ],
        purchaseRequestIds: [11],
    );

    expect($filteredItems)->toHaveCount(2)
        ->and(array_column($filteredItems, 'description'))->toBe([
            'Keep sourced item',
            'Keep manual item',
        ]);
});
