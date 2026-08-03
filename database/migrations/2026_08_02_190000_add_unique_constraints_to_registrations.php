<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('registrations'))
            ->pluck('name')
            ->all();

        Schema::table('registrations', function (Blueprint $table) use ($indexes) {
            if (! in_array('registrations_email_unique', $indexes, true)) {
                $table->unique('email');
            }

            // A previous version of this migration mistakenly created a unique
            // index named `phone` on the single `phone_country_code` column
            // (the second arg was treated as the index name). Drop it if present.
            if (in_array('phone', $indexes, true)) {
                $table->dropUnique('phone');
            }

            // composite unique on country code + phone number
            if (! in_array('registrations_phone_country_code_phone_unique', $indexes, true)) {
                $table->unique(['phone_country_code', 'phone']);
            }
        });
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('registrations'))
            ->pluck('name')
            ->all();

        Schema::table('registrations', function (Blueprint $table) use ($indexes) {
            if (in_array('registrations_email_unique', $indexes, true)) {
                $table->dropUnique(['email']);
            }
            if (in_array('registrations_phone_country_code_phone_unique', $indexes, true)) {
                $table->dropUnique('registrations_phone_country_code_phone_unique');
            }
        });
    }
};
