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
       Schema::create('case_report_item', function (Blueprint $table) {
    $table->id();
    $table->foreignId('case_report_id')->constrained()->onDelete('cascade');
    $table->foreignId('inventory_item_id')->constrained();
    $table->decimal('used_quantity', 10, 2); // الكمية التي استهلكت في هذه الحالة تحديداً
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_item');
    }
};
