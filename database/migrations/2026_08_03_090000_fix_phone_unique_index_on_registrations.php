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
            // Drop the mistaken single-column unique index (was named `phone`
            // but sat on `phone_country_code`) if it is still around.
            if (in_array('phone', $indexes, true)) {
                $table->dropUnique('phone');
            }

            // Ensure the intended composite unique exists.
            if (! in_array('registrations_phone_country_code_phone_unique', $indexes, true)) {
                $table->unique(['phone_country_code', 'phone']);
            }
        });
    }

    public function down(): void
    {
        // No-op: the original migration owns the composite index lifecycle.
    }
};
