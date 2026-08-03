<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->unique('email');
            $table->unique(['phone_country_code', 'phone']); // composite unique on country code + phone number
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropUnique('registrations_phone_country_code_phone_unique');
        });
    }
};
