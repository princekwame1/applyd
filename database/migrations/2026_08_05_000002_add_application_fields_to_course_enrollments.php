<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->string('serial_no')->nullable()->unique()->after('reference');
            $table->string('pin')->nullable()->after('serial_no');
            // Completed-application fields
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('education_level')->nullable();
            $table->text('goals')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn(['serial_no', 'pin', 'date_of_birth', 'gender', 'country', 'city', 'education_level', 'goals', 'completed_at']);
        });
    }
};
