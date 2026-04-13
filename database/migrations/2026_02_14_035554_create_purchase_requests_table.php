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
            /** 
             * Identifier
             * */
            $table->id();
            $table->string('number')
                ->index()
                ->unique()
            ;
            $table->unsignedTinyInteger('type')
                ->default(1)
                ->index()
            ;
            $table->text('description');
            $table->unsignedTinyInteger('status')
                ->default(1)
                ->index()
            ;

            /** 
             * Relation
             */
            // Delivery
            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('division_id')
                ->nullable()
                ->constrained('divisions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('project_id')
                ->nullable()
                ->constrained('projects')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('warehouse_address_id')
                ->nullable()
                ->constrained('warehouse_addresses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            // User
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;

            /** 
             * Other
             */
            $table->string('memo');
            $table->string('boq');
            $table->text('notes');
            $table->text('info');

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
