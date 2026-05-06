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
       Schema::create('case_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('patient_id')->constrained()->onDelete('cascade');
    $table->string('case_type'); // internal أو external
    
    // العلامات الحيوية
    $table->string('blood_pressure')->nullable();
    $table->string('sugar_level')->nullable();
    $table->string('oxygen_saturation')->nullable();
    
    // المالية
    $table->decimal('credit_price_at_time', 10, 2); // سعر النقطة وقت الحالة
    $table->decimal('total_paid', 10, 2); // المبلغ الذي دفعه المريض فعلياً
    
    $table->text('case_notes')->nullable(); // ملاحظات الممرض لهذه الزيارة
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_reports');
    }
};
