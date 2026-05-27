<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
public function run(): void
{
    \App\Models\InventoryItem::create([
        'name' => 'شاش معقم', 'quantity' => 100, 'unit' => 'قطعة', 'threshold' => 10, 'is_measurable' => true
    ]);
    \App\Models\InventoryItem::create([
        'name' => 'كحول طبي', 'quantity' => 5, 'unit' => 'ليتر', 'threshold' => 1, 'is_measurable' => false
    ]);
}
}
