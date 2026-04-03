<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('tax_type')
                ->nullable()
                ->after('discount');
            $table->decimal('tax_percentage', 5, 2)
                ->nullable()
                ->after('tax_type');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'tax_type',
                'tax_percentage',
            ]);
        });
    }
};
