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
    $table->string('full_name');
    $table->string('phone')->unique();
    $table->string('national_id')->nullable()->unique();
    $table->string('address')->nullable();
    $table->string('blood_type')->nullable();
    $table->text('chronic_diseases')->nullable(); // للأمراض المزمنة
    $table->text('current_medications')->nullable(); // للأدوية الحالية
    $table->text('extra_notes')->nullable(); // للعمليات السابقة أو ملاحظات خاصة
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
