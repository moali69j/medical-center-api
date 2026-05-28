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
    $table->string('case_type'); // internal, external
    $table->string('blood_pressure')->nullable();
    $table->string('sugar_level')->nullable();
    $table->string('oxygen_saturation')->nullable();
    $table->decimal('credit_price_at_time', 10, 2);
    $table->decimal('total_paid', 10, 2); // الكاش المستلم اليوم
    $table->decimal('total_cost_of_materials', 10, 2)->default(0.00); // تكلفة المواد المستهلكة
    $table->decimal('center_share', 10, 2)->default(0.00); // حصة المركز الصافية
    $table->decimal('staff_share', 10, 2)->default(0.00);  // حصة الكادر الصافية
    $table->text('visit_notes')->nullable(); // ملاحظات زيارة اليوم فقط
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
