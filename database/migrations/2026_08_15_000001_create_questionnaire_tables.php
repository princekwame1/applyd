<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Questionnaires — admin-built forms with a shareable public link.
 *
 * Deliberately its own set of tables rather than an extension of Pulse Check:
 * a check-in is a fixed short survey aimed at aggregate numbers, a
 * questionnaire is a general-purpose form (checkboxes, dropdowns, dates,
 * file uploads) whose value is the individual submission.
 *
 * Every foreign key is declared inside the CREATE, not by a later ALTER, so
 * they hold on SQLite as well as MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();   // the /forms/{slug} URL
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('success_message')->nullable();
            $table->string('submit_label', 60)->nullable();
            $table->boolean('is_published')->default(false);
            // An optional window. Null on either side means "no bound".
            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();
            // Optional cap on how many submissions the form accepts.
            $table->unsignedInteger('response_limit')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('questionnaire_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained()->cascadeOnDelete();
            // Answers are stored in a JSON map under this key, so it's set once
            // at creation and never editable afterwards.
            $table->string('key', 60);
            $table->string('type', 30);
            $table->string('label');
            $table->string('help_text')->nullable();
            $table->string('placeholder')->nullable();
            $table->json('options')->nullable();    // choice/checkbox/dropdown labels
            $table->json('settings')->nullable();   // per-type extras (max_select, max_kb, …)
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['questionnaire_id', 'key']);
        });

        Schema::create('questionnaire_responses', function (Blueprint $table) {
            $table->id();
            // restrict, not cascade: deleting a form must never quietly take the
            // submissions with it. The admin screen blocks that delete and
            // offers to close the form instead.
            $table->foreignId('questionnaire_id')->constrained()->restrictOnDelete();
            $table->string('reference', 20)->unique();
            $table->json('answers');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('questionnaire_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_response_id')->constrained()->cascadeOnDelete();
            $table->string('question_key', 60);
            // Private disk. Uploads are only ever served through the authorised
            // dashboard download route.
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index(['questionnaire_response_id', 'question_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questionnaire_files');
        Schema::dropIfExists('questionnaire_responses');
        Schema::dropIfExists('questionnaire_questions');
        Schema::dropIfExists('questionnaires');
    }
};
