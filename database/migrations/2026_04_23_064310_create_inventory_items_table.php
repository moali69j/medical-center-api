<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->decimal('quantity', 10, 2);
    $table->string('unit');
    $table->decimal('threshold', 10, 2);
    $table->boolean('is_measurable')->default(true);
    $table->decimal('cost_price', 10, 2)->default(0.00);   // سعر التكلفة لشراء القطعة
    $table->decimal('selling_price', 10, 2)->default(0.00); // سعر البيع كاش خارج الخدمة
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
