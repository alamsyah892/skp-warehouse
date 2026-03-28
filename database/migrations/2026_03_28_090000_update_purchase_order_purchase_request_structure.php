<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_order_purchase_request', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('purchase_request_id')
                ->constrained('purchase_requests')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['purchase_order_id', 'purchase_request_id'], 'po_pr_unique');
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->decimal('discount', 15, 2)->default(0)->after('notes');
        });

        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->decimal('discount', 15, 2)->default(0)->after('qty');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('discount', 15, 2)->default(0)->after('info');
            $table->decimal('tax', 15, 2)->default(0)->after('discount');
            $table->string('tax_description')->default('')->after('tax');
            $table->decimal('pembulatan', 15, 2)->default(0)->after('tax_description');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('discount', 15, 2)->default(0)->after('price');
        });

        DB::table('purchase_order_purchase_request')->insertUsing(
            ['purchase_order_id', 'purchase_request_id', 'created_at', 'updated_at'],
            DB::table('purchase_order_items')
                ->join('purchase_request_items', 'purchase_request_items.id', '=', 'purchase_order_items.purchase_request_item_id')
                ->selectRaw('DISTINCT purchase_order_items.purchase_order_id, purchase_request_items.purchase_request_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP')
        );

        DB::table('purchase_order_items')
            ->join('purchase_request_items', 'purchase_request_items.id', '=', 'purchase_order_items.purchase_request_item_id')
            ->update([
                'purchase_order_items.discount' => DB::raw('purchase_request_items.discount'),
            ]);
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'discount',
                'tax',
                'tax_description',
                'pembulatan',
            ]);
        });

        Schema::table('purchase_request_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('discount');
        });

        Schema::dropIfExists('purchase_order_purchase_request');
    }
};
