<?php

use App\Models\ItemCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_id')->nullable()
                ->constrained('item_categories')
                ->cascadeOnUpdate()
                ->nullOnDelete()
            ;
            $table->unsignedTinyInteger('level')
                ->default(ItemCategory::LEVEL_DOMAIN)
                ->index()
            ;
            // 1 = Domain
            // 2 = Category
            // 3 = Sub Category
            // 4 = Final Category (opsional)

            $table->string('code')->unique()->nullable();
            $table->string('name');
            $table->text('description');

            $table->boolean('allow_po')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_categories');
    }
};
