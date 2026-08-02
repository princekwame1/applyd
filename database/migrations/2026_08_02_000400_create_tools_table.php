<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->string('blurb')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        $blurbs = config('bootcamp.tool_blurbs', []);
        $rows = [];
        $i = 0;

        foreach (config('bootcamp.tool_categories', []) as $category => $tools) {
            foreach ($tools as $tool) {
                $rows[] = [
                    'name' => $tool,
                    'category' => $category,
                    'blurb' => $blurbs[$tool] ?? null,
                    'image' => null,
                    'sort_order' => ++$i,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('tools')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
