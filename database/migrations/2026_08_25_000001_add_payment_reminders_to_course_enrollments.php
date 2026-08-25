<?php

use App\Models\CourseEnrollment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            // The handle a payment-reminder link is built from. Deliberately
            // NOT `reference`: a reminder mints a fresh Paystack reference each
            // time it re-opens checkout, so the reference moves while the link
            // we texted out has to keep working.
            $table->string('pay_token', 32)->nullable()->unique()->after('reference');
            $table->timestamp('form_reminder_sent_at')->nullable()->after('credentials_sent_at');
            $table->timestamp('tuition_reminder_sent_at')->nullable()->after('form_reminder_sent_at');
        });

        // Everyone already registered gets a token too, so a reminder can go
        // out to the backlog this feature exists for.
        CourseEnrollment::withoutGlobalScopes()
            ->whereNull('pay_token')
            ->select('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $row->update(['pay_token' => Str::lower(Str::random(12))]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropColumn(['pay_token', 'form_reminder_sent_at', 'tuition_reminder_sent_at']);
        });
    }
};
