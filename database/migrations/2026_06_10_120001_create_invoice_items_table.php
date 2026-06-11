<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('goods_receive_item_id')
                ->nullable()
                ->constrained('goods_receive_items')
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

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
