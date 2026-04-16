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
        Schema::create('purchase_orders', function (Blueprint $table) {
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
            // Vendor
            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
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
             * Delivery
             */
            $table->date('delivery_date')->nullable()->default(null);
            $table->string('delivery_notes');
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->string('shipping_method');

            /** 
             * Other
             */
            $table->string('terms');
            $table->text('notes');
            $table->text('info');

            /** 
             * Amount & Calculation
             */
            $table->decimal('discount', 15, 2)->default(0);
            $table->string('tax_type');
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->string('tax_description');
            $table->decimal('rounding', 15, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
