<?php

use App\Http\Controllers\PrintPurchaseOrderKoController;
use App\Http\Controllers\PrintPurchaseOrderPoController;
use App\Http\Controllers\PrintPurchaseRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('purchase-requests/{purchaseRequest}/print', PrintPurchaseRequestController::class)
        ->name('purchase-requests.print');

    Route::get('purchase-orders/{purchaseOrder}/print-ko', PrintPurchaseOrderKoController::class)
        ->name('purchase-orders.print-ko');

    Route::get('purchase-orders/{purchaseOrder}/print-po', PrintPurchaseOrderPoController::class)
        ->name('purchase-orders.print-po');
});
