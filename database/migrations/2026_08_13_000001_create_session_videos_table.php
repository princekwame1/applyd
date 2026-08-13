<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // Normalised 11-char YouTube id, never the pasted URL — the public
            // page builds both the embed and the thumbnail URL from it.
            $table->string('youtube_id', 32);
            $table->string('session_label')->nullable();
            $table->text('description')->nullable();
            $table->date('recorded_on')->nullable();
            $table->string('thumbnail')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('youtube_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_videos');
    }
};
