<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // if (Schema::hasColumn('purchase_request_items', 'sort')) {
        //     Schema::table('purchase_request_items', function (Blueprint $table) {
        //         $table->dropColumn('sort');
        //     });
        // }

        // Schema::table('purchase_request_items', function (Blueprint $table) {
        //     $table->integer('sort')->default(0)->after('id');
        //     $table->index(['purchase_request_id', 'sort']);
        // });

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {

            DB::statement("
                UPDATE purchase_request_items
                SET sort = (
                    SELECT rn
                    FROM (
                        SELECT id,
                               ROW_NUMBER() OVER (
                                   PARTITION BY purchase_request_id
                                   ORDER BY id
                               ) AS rn
                        FROM purchase_request_items
                    ) x
                    WHERE x.id = purchase_request_items.id
                )
            ");

        } else { // mysql / mariadb

            DB::statement("
                UPDATE purchase_request_items t
                JOIN (
                    SELECT id,
                           ROW_NUMBER() OVER (
                               PARTITION BY purchase_request_id
                               ORDER BY id
                           ) AS rn
                    FROM purchase_request_items
                ) x ON x.id = t.id
                SET t.sort = x.rn
            ");

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('purchase_request_items', function (Blueprint $table) {
        //     $table->dropIndex(['purchase_request_id', 'sort']);
        //     $table->dropColumn('sort');
        // });
    }
};
