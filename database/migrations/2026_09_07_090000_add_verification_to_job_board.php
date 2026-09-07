<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Job poster identification and posting approval.
 *
 * Two independent review queues: a company is verified once (its Ghana Card
 * is checked against the contact person), and every job it posts is read
 * before it reaches the public board. Neither blocks the recruiter from using
 * their portal — what approval gates is public visibility, not their account.
 *
 * Everything already on the site is marked approved. A migration that dropped
 * every live company and job into a review queue would empty /jobs on deploy
 * and lock existing recruiters out of applicants they had already received —
 * a change nobody asked for, arriving unannounced. The card is left null for
 * them; it is only demanded of new signups.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Not unique: one person legitimately runs more than one business,
            // and a hard constraint would refuse that outright. The review
            // screen flags a card already on another account instead, which is
            // what an admin actually needs to see.
            $table->string('ghana_card', 20)->nullable()->after('name');
            $table->string('status', 20)->default('pending')->after('description');
            $table->timestamp('reviewed_at')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                ->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable()->after('reviewed_by');

            $table->index('status');
            $table->index('ghana_card');
        });

        Schema::table('job_openings', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('is_open');
            $table->timestamp('reviewed_at')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                ->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable()->after('reviewed_by');

            $table->index('status');
        });

        // Grandfather what is already live.
        DB::table('companies')->update(['status' => 'approved', 'reviewed_at' => now()]);
        DB::table('job_openings')->update(['status' => 'approved', 'reviewed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['status']);
            $table->dropIndex(['ghana_card']);
            $table->dropColumn(['ghana_card', 'status', 'reviewed_at', 'review_note']);
        });

        Schema::table('job_openings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'reviewed_at', 'review_note']);
        });
    }
};
