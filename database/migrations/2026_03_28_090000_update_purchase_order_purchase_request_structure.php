<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_order_purchase_request', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('purchase_request_id')
                ->constrained('purchase_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unique(['purchase_order_id', 'purchase_request_id'], 'po_pr_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_purchase_request');
    }
};