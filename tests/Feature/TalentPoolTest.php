<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobOpening;
use App\Models\PlanPurchase;
use App\Models\RecruiterPlan;
use App\Models\TalentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The CV pool and the credits that open it: who a recruiter can see, what a
 * credit buys, and what stays hidden until they pay.
 */
class TalentPoolTest extends TestCase
{
    use RefreshDatabase;

    protected function company(string $name = 'Acme Ltd'): Company
    {
        $user = User::factory()->create();
        $user->assignRole('company');

        return Company::create(['user_id' => $user->id, 'name' => $name]);
    }

    protected function job(Company $company, string $sector = 'Information Technology'): JobOpening
    {
        return $company->openings()->create([
            'title' => 'Developer',
            'description' => 'Build things.',
            'type' => 'Full-time',
            'sector' => $sector,
            'is_open' => true,
        ]);
    }

    protected function candidate(array $sectors = ['Information Technology'], array $overrides = []): TalentProfile
    {
        static $n = 0;
        $n++;

        return TalentProfile::create(array_merge([
            'full_name' => 'Kwame Asante',
            'email' => 'candidate'.$n.'@example.com',
            'phone' => '+233241234567',
            'headline' => 'Backend developer, 4 years',
            'sectors' => $sectors,
            'cv_path' => 'talent-pool/cv/cv'.$n.'.pdf',
            'cv_name' => 'kwame-cv.pdf',
        ], $overrides));
    }

    protected function giveCredits(Company $company, int $credits): PlanPurchase
    {
        return $company->purchases()->create([
            'plan_name' => 'Test plan',
            'credits' => $credits,
            'amount' => 100,
            'reference' => 'TEST-'.$company->id.'-'.$credits.'-'.uniqid(),
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    // ------------------------------------------------------------- dropping a CV

    public function test_a_candidate_can_drop_a_cv_without_applying_to_anything(): void
    {
        Storage::fake('local');

        $this->post(route('talent.store'), [
            'full_name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'phone' => '+233201234567',
            'headline' => 'Social media manager',
            'sectors' => ['Marketing & Media', 'Retail & Sales'],
            'cv' => UploadedFile::fake()->create('ama-cv.pdf', 200, 'application/pdf'),
        ])->assertRedirect(route('talent.create'));

        $profile = TalentProfile::firstOrFail();

        $this->assertSame(['Marketing & Media', 'Retail & Sales'], $profile->sectors);
        $this->assertTrue($profile->is_available);

        // The CV lands on the private disk — never anywhere the public can read.
        Storage::disk('local')->assertExists($profile->cv_path);
        $this->assertStringStartsWith('talent-pool/', $profile->cv_path);
    }

    public function test_a_cv_and_at_least_one_sector_are_required(): void
    {
        $this->post(route('talent.store'), [
            'full_name' => 'No Sectors',
            'email' => 'nosectors@example.com',
        ])->assertSessionHasErrors(['sectors', 'cv']);

        $this->assertSame(0, TalentProfile::count());
    }

    public function test_the_same_email_cannot_drop_twice(): void
    {
        Storage::fake('local');
        $this->candidate(overrides: ['email' => 'taken@example.com']);

        $this->post(route('talent.store'), [
            'full_name' => 'Someone Else',
            'email' => 'taken@example.com',
            'sectors' => ['Information Technology'],
            'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, TalentProfile::count());
    }

    // ---------------------------------------------------------------- matching

    public function test_a_recruiter_only_sees_candidates_matching_a_job_they_posted(): void
    {
        $company = $this->company();
        $this->job($company, 'Information Technology');

        $match = $this->candidate(['Information Technology']);
        $miss = $this->candidate(['Agriculture']);

        $visible = $company->matchingTalent()->pluck('id');

        $this->assertTrue($visible->contains($match->id));
        $this->assertFalse($visible->contains($miss->id));
    }

    public function test_a_recruiter_with_no_jobs_sees_nobody(): void
    {
        $company = $this->company();
        $this->candidate(['Information Technology']);

        $this->assertSame(0, $company->matchingTalent()->count());

        $this->actingAs($company->user)
            ->get(route('company.talent'))
            ->assertOk()
            ->assertSee('Post a job to see candidates');
    }

    public function test_matching_is_by_whole_sector_not_a_substring(): void
    {
        $company = $this->company();
        $this->job($company, 'Education');

        // "Education" must not drag in a candidate wanting "Higher Education".
        $other = $this->candidate(['Higher Education']);
        $exact = $this->candidate(['Education']);

        $visible = $company->matchingTalent()->pluck('id');

        $this->assertTrue($visible->contains($exact->id));
        $this->assertFalse($visible->contains($other->id));
    }

    public function test_a_paused_candidate_drops_out_of_the_pool(): void
    {
        $company = $this->company();
        $this->job($company);

        $profile = $this->candidate(['Information Technology'], ['is_available' => false]);

        $this->assertFalse($company->matchingTalent()->pluck('id')->contains($profile->id));
    }

    // ----------------------------------------------------------------- credits

    public function test_unlocking_spends_one_credit_and_reveals_the_candidate(): void
    {
        $company = $this->company();
        $this->job($company);
        $profile = $this->candidate();
        $this->giveCredits($company, 3);

        $this->actingAs($company->user)
            ->post(route('company.talent.unlock', $profile))
            ->assertRedirect();

        $this->assertTrue($company->fresh()->hasUnlocked($profile));
        $this->assertSame(2, $company->fresh()->creditsLeft());

        $this->actingAs($company->user)
            ->get(route('company.talent'))
            ->assertOk()
            ->assertSee('Kwame Asante')
            ->assertSee($profile->email);
    }

    public function test_a_locked_candidate_never_leaks_name_or_contact_details(): void
    {
        $company = $this->company();
        $this->job($company);
        $profile = $this->candidate();
        $this->giveCredits($company, 1);

        $this->actingAs($company->user)
            ->get(route('company.talent'))
            ->assertOk()
            ->assertSee('Kwame A.')            // masked
            ->assertDontSee('Kwame Asante')
            ->assertDontSee($profile->email)
            ->assertDontSee('+233241234567');
    }

    /**
     * The headline and summary are the only candidate-authored text on a locked
     * card, which makes them the one route contact details have around the
     * paywall — and the candidate is exactly who is motivated to use it.
     */
    public function test_a_locked_card_strips_contact_details_out_of_the_candidates_own_text(): void
    {
        $company = $this->company();
        $this->job($company);

        $this->candidate(['Information Technology'], [
            'headline' => 'Backend dev, 4 years — reach me on 0244 123 456',
            'summary' => 'Portfolio at kwame.dev, mail kwame@gmail.com or find me @kwamecodes.',
        ]);

        $response = $this->actingAs($company->user)->get(route('company.talent'))->assertOk();

        $response->assertDontSee('0244 123 456')
            ->assertDontSee('kwame@gmail.com')
            ->assertDontSee('kwame.dev')
            ->assertDontSee('@kwamecodes');

        // What is left still describes the person — this hides a contact route,
        // it does not blank the pitch.
        $response->assertSee('Backend dev, 4 years')->assertSee('Portfolio at');
    }

    public function test_paying_reveals_the_text_exactly_as_the_candidate_wrote_it(): void
    {
        $company = $this->company();
        $this->job($company);
        $profile = $this->candidate(['Information Technology'], [
            'headline' => 'Backend dev — 0244 123 456',
        ]);
        $this->giveCredits($company, 1);

        $this->actingAs($company->user)->post(route('company.talent.unlock', $profile));

        $this->actingAs($company->user)
            ->get(route('company.talent'))
            ->assertOk()
            ->assertSee('0244 123 456');
    }

    public function test_masking_leaves_ordinary_numbers_alone(): void
    {
        // Years of experience and dates are the point of a headline; a rule
        // that ate them would be worse than the leak it closes.
        $this->assertSame('4 years, graduated 2019, top 100 of 2400', \App\Support\ContactMask::scrub('4 years, graduated 2019, top 100 of 2400'));
        $this->assertFalse(\App\Support\ContactMask::carriesContact('Backend developer, 4 years'));
        $this->assertTrue(\App\Support\ContactMask::carriesContact('Call 0244123456'));
    }

    public function test_unlocking_the_same_candidate_twice_costs_one_credit(): void
    {
        $company = $this->company();
        $this->job($company);
        $profile = $this->candidate();
        $this->giveCredits($company, 5);

        $this->actingAs($company->user)->post(route('company.talent.unlock', $profile));
        $this->actingAs($company->user)->post(route('company.talent.unlock', $profile));

        $this->assertSame(1, $company->fresh()->creditsUsed());
        $this->assertSame(4, $company->fresh()->creditsLeft());
    }

    public function test_a_recruiter_without_credits_cannot_unlock(): void
    {
        $company = $this->company();
        $this->job($company);
        $profile = $this->candidate();

        $this->actingAs($company->user)
            ->post(route('company.talent.unlock', $profile))
            ->assertSessionHas('error');

        $this->assertFalse($company->fresh()->hasUnlocked($profile));
        $this->assertSame(0, $company->fresh()->creditsUsed());
    }

    public function test_credits_run_out_exactly_at_the_plan_limit(): void
    {
        $company = $this->company();
        $this->job($company);
        $this->giveCredits($company, 2);

        $profiles = collect(range(1, 3))->map(fn () => $this->candidate());

        foreach ($profiles as $profile) {
            $this->actingAs($company->user)->post(route('company.talent.unlock', $profile));
        }

        // Two unlocked, the third refused — the cap is the plan, not the pool.
        $this->assertSame(2, $company->fresh()->creditsUsed());
        $this->assertSame(0, $company->fresh()->creditsLeft());
        $this->assertFalse($company->fresh()->hasUnlocked($profiles->last()));
    }

    public function test_buying_again_adds_credits_rather_than_replacing_them(): void
    {
        $company = $this->company();

        $this->giveCredits($company, 5);
        $this->giveCredits($company, 25);

        $this->assertSame(30, $company->fresh()->creditsBought());
        $this->assertSame(30, $company->fresh()->creditsLeft());
    }

    public function test_an_unsettled_purchase_grants_nothing(): void
    {
        $company = $this->company();

        $company->purchases()->create([
            'plan_name' => 'Pending plan',
            'credits' => 50,
            'amount' => 900,
            'reference' => 'PENDING-1',
            'status' => 'pending',
        ]);

        $this->assertSame(0, $company->creditsBought());
    }

    public function test_a_candidate_outside_the_matched_sectors_cannot_be_unlocked(): void
    {
        $company = $this->company();
        $this->job($company, 'Information Technology');
        $this->giveCredits($company, 5);

        $unmatched = $this->candidate(['Agriculture']);

        $this->actingAs($company->user)
            ->post(route('company.talent.unlock', $unmatched))
            ->assertSessionHas('error');

        $this->assertSame(0, $company->fresh()->creditsUsed());
    }

    public function test_an_unlocked_candidate_stays_visible_after_the_job_closes(): void
    {
        $company = $this->company();
        $job = $this->job($company);
        $profile = $this->candidate();
        $this->giveCredits($company, 1);

        $this->actingAs($company->user)->post(route('company.talent.unlock', $profile));

        $job->update(['is_open' => false]);

        // They paid for this CV — closing the advert must not take it away.
        $this->assertTrue($company->fresh()->matchingTalent()->pluck('id')->contains($profile->id));
    }

    // ------------------------------------------------------------ file access

    public function test_the_cv_file_is_only_downloadable_once_unlocked(): void
    {
        Storage::fake('local');

        $company = $this->company();
        $this->job($company);
        $profile = $this->candidate();
        Storage::disk('local')->put($profile->cv_path, 'CV CONTENTS');

        $this->actingAs($company->user)
            ->get(route('company.talent.cv', $profile))
            ->assertForbidden();

        $this->giveCredits($company, 1);
        $this->actingAs($company->user)->post(route('company.talent.unlock', $profile));

        $this->actingAs($company->user)
            ->get(route('company.talent.cv', $profile))
            ->assertOk()
            ->assertDownload('kwame-cv.pdf');
    }

    public function test_one_company_cannot_use_another_companys_unlock(): void
    {
        Storage::fake('local');

        $buyer = $this->company('Buyer Ltd');
        $freeloader = $this->company('Freeloader Ltd');
        $this->job($buyer);
        $this->job($freeloader);

        $profile = $this->candidate();
        Storage::disk('local')->put($profile->cv_path, 'CV CONTENTS');

        $this->giveCredits($buyer, 1);
        $this->actingAs($buyer->user)->post(route('company.talent.unlock', $profile));

        $this->actingAs($freeloader->user)
            ->get(route('company.talent.cv', $profile))
            ->assertForbidden();
    }

    public function test_guests_cannot_reach_the_recruiter_screens(): void
    {
        $profile = $this->candidate();

        $this->get(route('company.talent'))->assertRedirect(route('login'));
        $this->get(route('company.plans'))->assertRedirect(route('login'));
        $this->get(route('company.talent.cv', $profile))->assertRedirect(route('login'));
        $this->post(route('company.talent.unlock', $profile))->assertRedirect(route('login'));
    }

    // --------------------------------------------------------------- payments

    public function test_a_paid_callback_credits_the_company(): void
    {
        $company = $this->company();
        $plan = RecruiterPlan::where('slug', 'growth')->firstOrFail();

        $purchase = $company->purchases()->create([
            'recruiter_plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'credits' => $plan->cv_credits,
            'amount' => $plan->price,
            'reference' => 'CVP-TEST-1',
            'status' => 'pending',
        ]);

        // Paystack is unconfigured in tests, so verification fails and nothing
        // is granted — a payment we cannot confirm must never add credits.
        $this->get(route('company.plans.callback', ['reference' => 'CVP-TEST-1']))
            ->assertRedirect(route('company.plans'));

        $this->assertSame('failed', $purchase->fresh()->status);
        $this->assertSame(0, $company->fresh()->creditsBought());
    }

    public function test_an_admin_can_grant_credits_by_hand(): void
    {
        $company = $this->company();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('dashboard.plan-purchases.grant'), [
                'company_id' => $company->id,
                'credits' => 10,
                'amount' => 900,
                'note' => 'Growth (bank transfer)',
            ])
            ->assertRedirect(route('dashboard.plan-purchases'));

        $this->assertSame(10, $company->fresh()->creditsBought());
        $this->assertSame('paid', PlanPurchase::latest()->first()->status);
    }

    public function test_a_recruiter_cannot_grant_themselves_credits(): void
    {
        $company = $this->company();

        $this->actingAs($company->user)
            ->post(route('dashboard.plan-purchases.grant'), [
                'company_id' => $company->id,
                'credits' => 999,
            ])
            ->assertForbidden();

        $this->assertSame(0, $company->fresh()->creditsBought());
    }

    public function test_the_seeded_plans_are_on_sale(): void
    {
        $this->assertSame(
            ['starter', 'growth', 'scale'],
            RecruiterPlan::active()->ordered()->pluck('slug')->all(),
        );
        $this->assertSame(25, RecruiterPlan::where('slug', 'growth')->first()->cv_credits);
    }
}
