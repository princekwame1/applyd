<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A temporary password has to actually be temporary. Accounts created with one
 * carry this flag, and RequirePasswordChange holds them on the "set your
 * password" screen until it's cleared — otherwise a password that was texted
 * in plain sight stays valid forever.
 *
 * Existing accounts default to false: nobody who already chose their own
 * password gets bounced.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->string('student_id', 20)->nullable()->unique()->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['must_change_password', 'student_id']);
        });
    }
};
