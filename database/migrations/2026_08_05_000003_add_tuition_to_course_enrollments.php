<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->string('tuition_option')->nullable();          // full | half
            $table->decimal('tuition_amount', 10, 2)->default(0);  // amount paid so far
            $table->string('tuition_status')->default('unpaid');   // unpaid | pending | partial | paid
            $table->string('tuition_reference')->nullable()->index();
            $table->timestamp('tuition_paid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn(['tuition_option', 'tuition_amount', 'tuition_status', 'tuition_reference', 'tuition_paid_at']);
        });
    }
};
