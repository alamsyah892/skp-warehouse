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
        Schema::create('company_division', function (Blueprint $table) {
            $table->id();

            $table->foreignId('division_id')
                ->constrained('divisions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;

            $table->unique(['division_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_division');
    }
};
