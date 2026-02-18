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
        Schema::create('item_category_vendor', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('item_category_id')
                ->constrained('item_categories')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;

            $table->unique(['vendor_id', 'item_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_category_vendor');
    }
};
