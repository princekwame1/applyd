<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->string('name')->nullable()->after('registration_id');
        });

        // Allow SMS not tied to a bootcamp registration (course enrolments, etc.)
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('registration_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
