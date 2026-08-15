<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conditional questions: "only ask this one if the answer to that one was …".
 *
 * The rule is stored as a small JSON object rather than a pair of columns
 * because it names another question by `key`, which is the same handle answers
 * are filed under — so a duplicated form carries its conditions across intact.
 *
 *   {"key": "employment_status", "operator": "in", "values": ["Employed"]}
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire_questions', function (Blueprint $table) {
            $table->json('visible_when')->nullable()->after('settings');
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire_questions', function (Blueprint $table) {
            $table->dropColumn('visible_when');
        });
    }
};
