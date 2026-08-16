<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student ID and a real account, issued the moment a course registration is
 * completed.
 *
 * This is a different thing from the existing Serial No + PIN: those are
 * *application* credentials for the enrolment portal on this site, and stop
 * mattering once the application is finished. A student ID is the person's
 * permanent handle, and `user_id` points at the shared account they sign in to
 * the learning portal with.
 *
 * `user_id` is added **only if it isn't already there**: the sibling
 * applyd-portal app introduced that column on the shared database and guards
 * its own migration the same way, so whichever repo migrates second is a no-op
 * and the live database never ends up with two.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('course_enrollments', 'user_id')) {
            Schema::table('course_enrollments', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('course_id')
                    ->constrained()->nullOnDelete();
            });
        }

        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->string('student_id', 20)->nullable()->after('reference');
            $table->timestamp('credentials_sent_at')->nullable()->after('completed_at');

            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table) {
            $table->dropIndex(['student_id']);
            $table->dropColumn(['student_id', 'credentials_sent_at']);
        });

        // `user_id` is deliberately left alone — the portal owns it.
    }
};
