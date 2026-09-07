<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two account-level facts the facilitator screens need, both on `users`
 * because they belong to the person rather than to any one class or intake.
 *
 * `phone` is what an SMS goes to. A student's number lives on their
 * registration, but a facilitator never fills in a registration — somebody in
 * the office creates the account for them — so there was nowhere to put it.
 *
 * `credentials_sent_at` answers "have we actually let them in yet?". Without
 * it the only record that a login went out is an email log, which is the wrong
 * shape for a column, a filter, or the question an admin is really asking.
 *
 * `users` is a shared table, so both columns are mirrored into the portal's
 * create_shared_tables migration — that file stands the table up for the
 * portal's own test database and has to keep matching this one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 40)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'credentials_sent_at')) {
                $table->timestamp('credentials_sent_at')->nullable()->after('must_change_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'credentials_sent_at']);
        });
    }
};
