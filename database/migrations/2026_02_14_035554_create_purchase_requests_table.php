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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('warehouse_address_id')->nullable()
                ->constrained('warehouse_addresses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('division_id')
                ->constrained('divisions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;

            $table->unsignedTinyInteger('type')
                ->default(1)
                ->index()
            ;

            $table->string('number')->unique(); //->nullable();
            $table->text('description');
            $table->string('memo');
            $table->string('boq');
            $table->text('notes');

            $table->text('info');

            $table->unsignedTinyInteger('status')
                ->default(1)
                ->index()
            ;

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
