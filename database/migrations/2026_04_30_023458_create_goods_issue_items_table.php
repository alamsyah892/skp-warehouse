<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('goods_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_issue_id')
                ->constrained('goods_issues')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->decimal('qty', 15, 2)->default(0);
            $table->text('description');
            $table->unsignedInteger('sort')->default(0)->index();
            $table->timestamps();
            $table->index(['goods_issue_id', 'item_id'], 'gi_items_gi_item_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_issue_items');
    }
};
