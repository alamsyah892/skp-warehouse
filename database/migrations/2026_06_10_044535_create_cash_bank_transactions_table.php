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
        Schema::create('cash_bank_transactions', function (Blueprint $table) {
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

            /** 
             * Relation
             */
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('bank_id')
                ->constrained('banks')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;
            $table->foreignId('project_id')
                ->nullable()
                ->constrained('projects')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
            ;

            /** 
             * Other
             */
            $table->date('voucher_date')
                ->index()
                ->nullable()
                ->default(null)
            ;
            $table->string('check_number');
            $table->decimal('total_amount', 15, 2)
                ->default(0)
            ;

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
        Schema::dropIfExists('cash_bank_transactions');
    }
};
