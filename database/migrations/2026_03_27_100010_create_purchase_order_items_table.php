<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('purchase_request_item_id')
                ->nullable()
                ->constrained('purchase_request_items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;

            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->text('description');
            $table->unsignedInteger('sort')->default(0)->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
