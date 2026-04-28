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
        Schema::create('goods_receive_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('goods_receive_id')
                ->constrained('goods_receives')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('purchase_order_item_id')
                ->nullable()
                ->constrained('purchase_order_items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                // ->nullOnDelete()
            ;

            $table->decimal('qty', 15, 2)->default(0);
            $table->text('description');
            $table->unsignedInteger('sort')->default(0)->index();

            $table->timestamps();

            $table->index(['goods_receive_id', 'purchase_order_item_id'], 'gr_items_gr_po_item_idx');
            $table->index(['goods_receive_id', 'item_id'], 'gr_items_gr_item_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receive_items');
    }
};
