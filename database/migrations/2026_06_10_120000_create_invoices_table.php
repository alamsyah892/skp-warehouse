<?php

use App\Enums\PurchaseOrderTaxType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            /** 
             * Identifier
             * */
            $table->id();
            $table->string('number')
                ->index()
                ->unique()
            ;
            $table->text('description');

            /** 
             * Relation
             */
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('goods_receive_id')
                ->constrained('goods_receives')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;

            /** 
             * Other
             */
            $table->date('invoice_date')
                ->index()
                ->nullable()
                ->default(null)
            ;
            $table->date('invoice_due_date')
                ->index()
                ->nullable()
                ->default(null)
            ;

            $table->text('notes');
            $table->text('info');

            /** 
             * Amount & Calculation
             */
            $table->decimal('discount', 15, 2)->default(0);
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

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
