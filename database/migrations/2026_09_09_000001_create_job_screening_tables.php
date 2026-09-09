<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What a recruiter says the role needs. Two kinds live in one table
        // because they are scored together and a screen that showed them
        // apart would be a screen that could disagree with the score.
        Schema::create('job_screening_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_opening_id')->constrained()->cascadeOnDelete();
            // 'keyword' is matched against the CV and cover letter and is never
            // shown to the applicant; 'question' is asked on the apply form.
            $table->string('kind', 20)->default('keyword');
            // Answers are filed under this, so it is set once at creation and
            // never edited — changing it would orphan every answer collected.
            $table->string('key', 60)->nullable();
            $table->string('label', 160);
            $table->string('answer_type', 20)->nullable();
            $table->json('options')->nullable();
            $table->json('expected')->nullable();
            $table->unsignedTinyInteger('weight')->default(1);
            // A must-have. It flags the applicant, it never rejects them —
            // see App\Support\FitScore.
            $table->boolean('is_knockout')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['job_opening_id', 'key']);
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->json('screening_answers')->nullable()->after('cover_letter');
            // Null means nothing could be checked — no criteria, or none that
            // applied. It is not the same as zero and must not read as it.
            $table->unsignedTinyInteger('fit_score')->nullable()->after('screening_answers');
            $table->json('fit_breakdown')->nullable()->after('fit_score');
            $table->boolean('meets_requirements')->nullable()->after('fit_breakdown');
            // Best-effort text of the uploaded CV, kept so a keyword can be
            // re-scored later without re-reading the file off disk.
            $table->longText('cv_text')->nullable()->after('cv_name');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn([
                'screening_answers', 'fit_score', 'fit_breakdown', 'meets_requirements', 'cv_text',
            ]);
        });

        Schema::dropIfExists('job_screening_criteria');
    }
};
