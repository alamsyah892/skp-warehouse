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
        Schema::create('project_warehouse', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;

            $table->unique(['project_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_warehouse');
    }
};
