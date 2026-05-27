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
       Schema::create('service_materials', function (Blueprint $table) {
    $table->id();
    $table->foreignId('service_id')->constrained()->onDelete('cascade');
    $table->foreignId('inventory_item_id')->constrained()->onDelete('cascade');
    $table->decimal('quantity', 10, 2); // الكمية المستهلكة من هذه المادة لهذه الخدمة
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_materials');
    }
};
