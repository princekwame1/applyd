<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recruiter plans + the talent pool they buy access to.
 *
 * Candidates drop a CV and tag it with the sectors they want work in, without
 * applying to anything. A company sees a candidate once it has published a job
 * in a matching sector — and can only open the CV by spending one of the
 * unlock credits its plan came with.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruiter_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 60)->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('cv_credits')->default(0);
            $table->string('blurb')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // The plan can be renamed, repriced or retired later; what was
            // bought is settled at purchase time, so it's snapshotted here.
            $table->foreignId('recruiter_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plan_name');
            $table->unsignedInteger('credits');
            $table->decimal('amount', 10, 2);
            $table->string('reference', 64)->unique();
            $table->string('status', 20)->default('pending');   // pending | paid | failed
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('talent_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('headline')->nullable();
            $table->string('location')->nullable();
            $table->json('sectors');            // the sectors this person wants work in
            $table->text('summary')->nullable();
            $table->string('cv_path');          // private disk, never public
            $table->string('cv_name');
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique('email');
        });

        Schema::create('cv_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('talent_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // One credit per candidate per company — re-opening a CV they have
            // already paid for must never charge them twice.
            $table->unique(['company_id', 'talent_profile_id']);
        });

        $now = now();

        DB::table('recruiter_plans')->insert([
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => 250.00,
                'cv_credits' => 5,
                'blurb' => 'A first look at the talent pool.',
                'features' => json_encode(['5 CV unlocks', 'Unlimited job posts', 'Applications to your posts stay free']),
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'price' => 900.00,
                'cv_credits' => 25,
                'blurb' => 'For teams hiring across a few roles.',
                'features' => json_encode(['25 CV unlocks', 'Unlimited job posts', 'Applications to your posts stay free']),
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Scale',
                'slug' => 'scale',
                'price' => 2500.00,
                'cv_credits' => 100,
                'blurb' => 'Steady hiring, all year round.',
                'features' => json_encode(['100 CV unlocks', 'Unlimited job posts', 'Applications to your posts stay free']),
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_unlocks');
        Schema::dropIfExists('talent_profiles');
        Schema::dropIfExists('plan_purchases');
        Schema::dropIfExists('recruiter_plans');
    }
};
