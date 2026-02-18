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
        Schema::create('company_project', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;

            $table->unique(['project_id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_project');
    }
};
