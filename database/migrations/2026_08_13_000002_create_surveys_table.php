<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Surveys become rows instead of a fixed pair in config/surveys.php, so admins
 * can run one per session/cohort. Questions and responses move from the
 * `survey_type` slug to a real foreign key — every existing question and every
 * answer already collected is carried across to the matching survey row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();   // the /check-in/{slug} URL
            $table->string('name');
            $table->string('eyebrow')->nullable();
            $table->string('blurb')->nullable();
            $table->string('thanks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $this->seedSurveys();

        $ids = DB::table('surveys')->pluck('id', 'slug');

        // --- survey_questions: survey_type -> survey_id ----------------------
        Schema::table('survey_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_id')->nullable()->after('id');
        });

        foreach ($ids as $slug => $id) {
            DB::table('survey_questions')->where('survey_type', $slug)->update(['survey_id' => $id]);
        }

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropUnique(['survey_type', 'key']);
            $table->dropColumn('survey_type');
        });

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_id')->nullable(false)->change();
            $table->unique(['survey_id', 'key']);
            // Integrity for MySQL. SQLite ignores foreign keys added by ALTER
            // TABLE, so the app deletes questions itself rather than trusting
            // the cascade (see SurveyManagerController::destroy).
            $table->foreign('survey_id')->references('id')->on('surveys')->cascadeOnDelete();
        });

        // --- survey_responses: survey_type -> survey_id ----------------------
        Schema::table('survey_responses', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_id')->nullable()->after('id');
        });

        foreach ($ids as $slug => $id) {
            DB::table('survey_responses')->where('survey_type', $slug)->update(['survey_id' => $id]);
        }

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropIndex(['survey_type']);
            $table->dropColumn('survey_type');
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->unsignedBigInteger('survey_id')->nullable(false)->change();
            $table->index('survey_id');
            // restrict, not cascade: deleting a survey must never take collected
            // answers with it. The admin screen blocks the delete and offers to
            // archive instead — that check is the real guard, this backs it up
            // on MySQL (SQLite ignores foreign keys added by ALTER TABLE).
            $table->foreign('survey_id')->references('id')->on('surveys')->restrictOnDelete();
        });
    }

    /**
     * The configured check-ins, plus anything already present in the data that
     * config doesn't know about — nothing gets left without a survey to hang on.
     */
    private function seedSurveys(): void
    {
        $now = now();
        $order = 0;
        $rows = [];

        foreach (config('surveys.types', []) as $slug => $copy) {
            $rows[$slug] = [
                'slug' => $slug,
                'name' => $copy['label'] ?? Str::headline($slug),
                'eyebrow' => $copy['eyebrow'] ?? null,
                'blurb' => $copy['blurb'] ?? null,
                'thanks' => $copy['thanks'] ?? null,
                'is_active' => true,
                'sort_order' => ++$order,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $inUse = DB::table('survey_questions')->distinct()->pluck('survey_type')
            ->merge(DB::table('survey_responses')->distinct()->pluck('survey_type'))
            ->filter()
            ->unique();

        foreach ($inUse as $slug) {
            if (! isset($rows[$slug])) {
                $rows[$slug] = [
                    'slug' => $slug,
                    'name' => Str::headline($slug),
                    'eyebrow' => null,
                    'blurb' => null,
                    'thanks' => null,
                    'is_active' => true,
                    'sort_order' => ++$order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows) {
            DB::table('surveys')->insert(array_values($rows));
        }
    }

    public function down(): void
    {
        $slugs = DB::table('surveys')->pluck('slug', 'id');

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropForeign(['survey_id']);
            $table->string('survey_type', 20)->nullable()->after('id');
        });

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropForeign(['survey_id']);
            $table->dropUnique(['survey_id', 'key']);
            $table->string('survey_type', 20)->nullable()->after('id');
        });

        foreach ($slugs as $id => $slug) {
            DB::table('survey_questions')->where('survey_id', $id)->update(['survey_type' => $slug]);
            DB::table('survey_responses')->where('survey_id', $id)->update(['survey_type' => $slug]);
        }

        Schema::table('survey_questions', function (Blueprint $table) {
            $table->dropColumn('survey_id');
            $table->string('survey_type', 20)->nullable(false)->change();
            $table->unique(['survey_type', 'key']);
        });

        Schema::table('survey_responses', function (Blueprint $table) {
            $table->dropIndex(['survey_id']);
            $table->dropColumn('survey_id');
            $table->string('survey_type', 20)->nullable(false)->change();
            $table->index('survey_type');
        });

        Schema::dropIfExists('surveys');
    }
};
