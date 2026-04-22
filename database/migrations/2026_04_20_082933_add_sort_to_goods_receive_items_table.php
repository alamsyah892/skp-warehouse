<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // if (Schema::hasColumn('goods_receive_items', 'sort')) {
        //     Schema::table('goods_receive_items', function (Blueprint $table) {
        //         $table->dropColumn('sort');
        //     });
        // }

        // Schema::table('goods_receive_items', function (Blueprint $table) {
        //     $table->integer('sort')->default(0)->after('id');
        //     $table->index(['goods_receive_id', 'sort']);
        // });

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {

            DB::statement("
                UPDATE goods_receive_items
                SET sort = (
                    SELECT rn
                    FROM (
                        SELECT id,
                               ROW_NUMBER() OVER (
                                   PARTITION BY goods_receive_id
                                   ORDER BY id
                               ) AS rn
                        FROM goods_receive_items
                    ) x
                    WHERE x.id = goods_receive_items.id
                )
            ");

        } else { // mysql / mariadb

            DB::statement("
                UPDATE goods_receive_items t
                JOIN (
                    SELECT id,
                           ROW_NUMBER() OVER (
                               PARTITION BY goods_receive_id
                               ORDER BY id
                           ) AS rn
                    FROM goods_receive_items
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
        // Schema::table('goods_receive_items', function (Blueprint $table) {
        //     $table->dropIndex(['goods_receive_id', 'sort']);
        //     $table->dropColumn('sort');
        // });
    }
};
