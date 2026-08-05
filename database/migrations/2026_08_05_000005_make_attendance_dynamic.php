<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->json('attendance')->nullable()->after('price');
        });

        // Migrate the previous fixed columns into the dynamic list.
        foreach (DB::table('courses')->get() as $course) {
            $options = [];
            if ($course->price_in_person !== null) {
                $options[] = ['label' => 'In-Person', 'price' => (float) $course->price_in_person];
            }
            if ($course->price_online !== null) {
                $options[] = ['label' => 'Online', 'price' => (float) $course->price_online];
            }
            if ($course->price_hybrid !== null) {
                $options[] = ['label' => 'Hybrid', 'price' => (float) $course->price_hybrid];
            }
            if ($options) {
                DB::table('courses')->where('id', $course->id)->update(['attendance' => json_encode($options)]);
            }
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['price_in_person', 'price_online', 'price_hybrid']);
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->decimal('price_in_person', 10, 2)->nullable();
            $table->decimal('price_online', 10, 2)->nullable();
            $table->decimal('price_hybrid', 10, 2)->nullable();
            $table->dropColumn('attendance');
        });
    }
};
