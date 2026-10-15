<?php

use App\Enums\PurchaseOrderTaxType;
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

            $table->text('po_opening_letter'); // 0
            $table->text('po_payment_terms'); // 1
            $table->text('po_processing_date'); // 2
            $table->text('po_payment_method'); // 3
            $table->text('po_description'); // 4
            $table->text('po_terms'); // 5
            $table->text('po_billing_method'); // 6
            $table->text('po_opname_method'); // 8
            $table->text('po_processing_time'); // 9
            $table->text('po_location'); // 11
            $table->text('po_miscellaneous'); // 12
            $table->text('po_warranty'); // 13
            $table->text('po_duration_info'); // 14
            $table->text('po_carbon_copy'); // 15
            $table->text('po_footnote'); // 16

            /** 
             * Other
             */
            $table->text('notes');
            $table->text('info');

            /** 
             * Amount & Calculation
             */
            $table->decimal('discount', 15, 2)
                ->default(0)
            ;
            $table->string('tax_type')
                ->default(PurchaseOrderTaxType::EXCLUDE->value)
            ;
            $table->unsignedTinyInteger('tax_percentage')
                ->default(1)
            ;
            $table->string('tax_description');
            $table->decimal('rounding', 15, 2)
                ->default(0)
            ;
            $table->decimal('total_amount', 15, 2)
                ->default(0)
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
        Schema::dropIfExists('purchase_orders');
    }
};
