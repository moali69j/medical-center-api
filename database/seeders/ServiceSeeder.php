<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    \App\Models\Service::create(['name' => 'فتح قسطرة', 'credits_required' => 50]);
    \App\Models\Service::create(['name' => 'ضرب إبرة عضل', 'credits_required' => 10]);
    \App\Models\Service::create(['name' => 'غيار جرح بسيط', 'credits_required' => 20]);
}
}
