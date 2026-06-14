<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ServiceSeeder::class,
            InventorySeeder::class,
        ]);

        // حماية مكررة: استخدام updateOrCreate لضمان عدم حدوث خطأ مفاتيح مكررة
        Setting::updateOrCreate(
            ['key' => 'credit_price'],
            ['value' => '1000']
        );
    }
}