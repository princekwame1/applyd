<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_questions', function (Blueprint $table) {
            $table->id();
            $table->string('survey_type', 20);
            $table->string('key', 60);
            $table->string('type', 20);           // choice | scale | text
            $table->string('prompt');
            $table->json('options')->nullable();  // choice options, or scale labels
            $table->string('placeholder')->nullable();
            $table->boolean('required')->default(true);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Answers are keyed by `key` inside survey_responses.answers, so the
            // key has to be unique within a survey for that map to be unambiguous.
            $table->unique(['survey_type', 'key']);
        });

        Schema::create('survey_responses', function (Blueprint $table) {
            $table->id();
            $table->string('survey_type', 20)->index();
            $table->json('answers');
            $table->timestamps();
        });

        $now = now();
        $rows = [];
        $order = [];

        foreach (config('surveys.questions', []) as $surveyType => $questions) {
            foreach ($questions as $q) {
                $order[$surveyType] = ($order[$surveyType] ?? 0) + 1;

                $rows[] = [
                    'survey_type' => $surveyType,
                    'key' => $q['key'],
                    'type' => $q['type'],
                    'prompt' => $q['prompt'],
                    'options' => isset($q['options']) ? json_encode($q['options']) : null,
                    'placeholder' => $q['placeholder'] ?? null,
                    'required' => $q['required'] ?? true,
                    'active' => true,
                    'sort_order' => $order[$surveyType],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows) {
            DB::table('survey_questions')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('survey_questions');
    }
};
