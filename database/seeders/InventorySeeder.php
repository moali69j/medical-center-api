<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InventoryItem;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InventoryItem::create([
            'name' => 'شاش معقم', 
            'quantity' => 100, 
            'unit' => 'قطعة', 
            'threshold' => 10, 
            'is_measurable' => true,
            'cost_price' => 500,     // تكلفة الشراء على المركز ل حساب صافي الأرباح
        ]);

        InventoryItem::create([
            'name' => 'كحول طبي', 
            'quantity' => 5, 
            'unit' => 'ليتر', 
            'threshold' => 1, 
            'is_measurable' => false,
            'cost_price' => 5000, 
        ]);
    }
}