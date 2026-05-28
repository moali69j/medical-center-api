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
       Schema::create('patients', function (Blueprint $table) {
    $table->id();
    $table->string('full_name')->index(); // إضافة index للبحث السريع
    $table->string('phone')->nullable()->index(); // قابل للبحث والغياب
    $table->string('national_id')->nullable()->unique()->index(); 
    $table->string('address')->nullable();
    $table->string('blood_type')->nullable();
    $table->text('chronic_diseases')->nullable();
    $table->text('current_medications')->nullable();
    $table->text('permanent_medical_notes')->nullable(); // الملاحظات الدائمة والعمليات
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
