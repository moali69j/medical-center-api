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
        Schema::table('case_reports', function (Blueprint $table) {
            $table->boolean('is_paid_to_staff')->default(false)->after('staff_share');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('case_reports', function (Blueprint $table) {
            $table->dropColumn('is_paid_to_staff');
        });
    }
};
