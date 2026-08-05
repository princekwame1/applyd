<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->decimal('price_in_person', 10, 2)->nullable()->after('price');
            $table->decimal('price_online', 10, 2)->nullable()->after('price_in_person');
            $table->decimal('price_hybrid', 10, 2)->nullable()->after('price_online');
        });

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->string('attendance_type')->nullable()->after('course_id');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['price_in_person', 'price_online', 'price_hybrid']);
        });
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn('attendance_type');
        });
    }
};
