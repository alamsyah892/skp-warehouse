<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vendor_id')
                ->constrained('vendors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

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
            $table->string('termin');
            $table->string('delivery_info');
            $table->text('notes');

            $table->text('info');

            $table->unsignedTinyInteger('status')
                ->default(1)
                ->index()
            ;

            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->string('tax_description');
            $table->decimal('rounding', 15, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
