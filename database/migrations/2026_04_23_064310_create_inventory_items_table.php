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
    $table->decimal('quantity', 10, 2)->default(0); // الكمية المتاحة
    $table->string('unit'); // قطعة، ليتر، إلخ
    $table->decimal('threshold', 10, 2)->default(5); // حد العتبة للتنبيه
    $table->boolean('is_measurable')->default(true); // هل تُخصم آلياً أم يدوياً؟
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
