<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('week_label', 100);
            $table->string('focus');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('schedules')->insert(collect([
            ['Weeks 1–2', 'Task & Project Management — Trello, Basecamp, Notion'],
            ['Weeks 2–3', 'Communication & Time Management — Slack, Google Calendar, Google Meet'],
            ['Weeks 3–4', 'AI & Automation — ChatGPT, Claude, Gemini, Copilot, Perplexity'],
            ['Weeks 4–5', 'Documents & Cloud Collaboration — Google Drive, Docs, Slides, Forms'],
            ['Weeks 5–6', 'Creative & Design — Canva, Adobe Creative Cloud, CapCut'],
            ['Weeks 6–7', 'Social Media & Marketing — Buffer, Hootsuite, SMS Marketing'],
            ['Week 8', 'Live Events, Masterclass & Graduation'],
        ])->map(fn ($row, $i) => [
            'week_label' => $row[0],
            'focus' => $row[1],
            'sort_order' => $i + 1,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
