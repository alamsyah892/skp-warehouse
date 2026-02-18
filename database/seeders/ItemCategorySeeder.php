<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        // LEVEL 1 - DOMAIN
        $konstruksi = ItemCategory::firstOrCreate([
            'name' => 'Konstruksi',
            'description' => '',
            'code' => 'KON',
            'level' => 1,
        ]);

        $atk = ItemCategory::firstOrCreate([
            'name' => 'Alat Tulis Kantor',
            'description' => '',
            'code' => 'ATK',
            'level' => 1,
        ]);

        $operasional = ItemCategory::firstOrCreate([
            'name' => 'Operasional',
            'description' => '',
            'code' => 'OPS',
            'level' => 1,
        ]);

        $it = ItemCategory::firstOrCreate([
            'name' => 'IT Supply',
            'description' => '',
            'code' => 'IT',
            'level' => 1,
        ]);

        // LEVEL 2
        // $ac = ItemCategory::firstOrCreate([
        //     'name' => 'AC',
        //     'description' => '',
        //     'code' => 'AC',
        //     'level' => 2,
        //     'parent_id' => $konstruksi->id,
        // ]);
        // $fan = ItemCategory::firstOrCreate([
        //     'name' => 'FAN',
        //     'description' => '',
        //     'code' => 'FAN',
        //     'level' => 2,
        //     'parent_id' => $konstruksi->id,
        // ]);
        // $filter = ItemCategory::firstOrCreate([
        //     'name' => 'FILTER',
        //     'description' => '',
        //     'code' => 'FTR',
        //     'level' => 2,
        //     'parent_id' => $konstruksi->id,
        // ]);

        // $kertasA4 = ItemCategory::firstOrCreate([
        //     'name' => 'Kertas HVS A4',
        //     'description' => '',
        //     'code' => 'KA4',
        //     'level' => 2,
        //     'parent_id' => $atk->id,
        // ]);
        // $mapA4 = ItemCategory::firstOrCreate([
        //     'name' => 'Map Coklat A4',
        //     'description' => '',
        //     'code' => 'MA4',
        //     'level' => 2,
        //     'parent_id' => $atk->id,
        // ]);
        // $pulpen = ItemCategory::firstOrCreate([
        //     'name' => 'Ballpoint Biru',
        //     'description' => '',
        //     'code' => 'BPN',
        //     'level' => 2,
        //     'parent_id' => $atk->id,
        // ]);

        // $sabun = ItemCategory::firstOrCreate([
        //     'name' => 'Sabun Cuci Piring',
        //     'description' => '',
        //     'code' => 'KBS',
        //     'level' => 2,
        //     'parent_id' => $operasional->id,
        // ]);
        // $tissue = ItemCategory::firstOrCreate([
        //     'name' => 'Tissue Muka',
        //     'description' => '',
        //     'code' => 'TIS',
        //     'level' => 2,
        //     'parent_id' => $operasional->id,
        // ]);

        // $hardware = ItemCategory::firstOrCreate([
        //     'name' => 'Hardware',
        //     'description' => '',
        //     'code' => 'HW',
        //     'level' => 2,
        //     'parent_id' => $it->id,
        // ]);
        // $software = ItemCategory::firstOrCreate([
        //     'name' => 'Software',
        //     'description' => '',
        //     'code' => 'SW',
        //     'level' => 2,
        //     'parent_id' => $it->id,
        // ]);

        // LEVEL 3
    }
}
