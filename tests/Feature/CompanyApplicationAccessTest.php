<?php

namespace Tests\Feature;

use App\Models\ApplicationDocument;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Reviewing the people who applied to your own job posts is what a plan buys.
 *
 * Posting a job stays free — an empty public board helps nobody — but the
 * applicant list, the status control and both file downloads are behind the
 * paywall. The downloads matter most: a gate on the list alone is decoration
 * while the CV sits one guessable id away.
 */
class CompanyApplicationAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function company(string $name = 'Acme Ltd'): Company
    {
        $user = User::factory()->create();
        $user->assignRole('company');

        return Company::create(['user_id' => $user->id, 'name' => $name]);
    }

    protected function job(Company $company): JobOpening
    {
        return $company->openings()->create([
            'title' => 'Developer',
            'description' => 'Build things.',
            'type' => 'Full-time',
            'sector' => 'Information Technology',
            'is_open' => true,
        ]);
    }

    protected function application(JobOpening $opening): JobApplication
    {
        return $opening->applications()->create([
            'full_name' => 'Kwame Asante',
            'email' => 'kwame@example.com',
            'phone' => '+233241234567',
            'cv_path' => 'applications/cv.pdf',
            'cv_name' => 'kwame-cv.pdf',
            'status' => 'new',
        ]);
    }

    protected function buyPlan(Company $company, int $credits = 5): void
    {
        $company->purchases()->create([
            'plan_name' => 'Starter',
            'credits' => $credits,
            'amount' => 100,
            'reference' => 'TEST-'.$company->id.'-'.uniqid(),
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    // ------------------------------------------------------------ without a plan

    public function test_a_company_with_no_plan_is_sent_to_the_plans_page(): void
    {
        $company = $this->company();
        $opening = $this->job($company);

        $this->actingAs($company->user)
            ->get(route('company.applications', $opening))
            ->assertRedirect(route('company.plans'))
            ->assertSessionHas('error');
    }

    /**
     * The one that actually matters. Blocking the list while leaving the file
     * routes open would mean the CV is still one guessable id away.
     */
    public function test_the_cv_and_document_downloads_are_behind_the_same_gate(): void
    {
        Storage::fake('local');

        $company = $this->company();
        $application = $this->application($this->job($company));
        $document = ApplicationDocument::create([
            'job_application_id' => $application->id,
            'path' => 'applications/extra.pdf',
            'original_name' => 'certificate.pdf',
        ]);

        $this->actingAs($company->user)
            ->get(route('company.applications.cv', $application))
            ->assertRedirect(route('company.plans'));

        $this->actingAs($company->user)
            ->get(route('company.applications.document', $document))
            ->assertRedirect(route('company.plans'));
    }

    public function test_the_status_control_is_behind_the_gate_too(): void
    {
        $company = $this->company();
        $application = $this->application($this->job($company));

        $this->actingAs($company->user)
            ->patch(route('company.applications.status', $application), ['status' => 'shortlisted'])
            ->assertRedirect(route('company.plans'));

        $this->assertSame('new', $application->fresh()->status);
    }

    /** Posting is free — the paywall sits on reviewing, not on publishing. */
    public function test_posting_a_job_still_works_without_a_plan(): void
    {
        $company = $this->company();

        $this->actingAs($company->user)->post(route('company.jobs.store'), [
            'title' => 'Data Analyst',
            'description' => 'Crunch numbers.',
            'type' => 'Full-time',
            'sector' => 'Information Technology',
        ])->assertRedirect(route('company.home'));

        $this->assertSame(1, $company->openings()->count());
    }

    /** The count stays visible unpaid — it is the reason to buy. */
    public function test_the_dashboard_still_shows_how_many_have_applied(): void
    {
        $company = $this->company();
        $this->application($this->job($company));

        $this->actingAs($company->user)
            ->get(route('company.home'))
            ->assertOk()
            ->assertSee('1 application')
            ->assertSee('is-locked', false);
    }

    // --------------------------------------------------------------- with a plan

    public function test_a_company_on_a_plan_sees_its_applicants(): void
    {
        $company = $this->company();
        $opening = $this->job($company);
        $this->application($opening);
        $this->buyPlan($company);

        $this->actingAs($company->user)
            ->get(route('company.applications', $opening))
            ->assertOk()
            ->assertSee('Kwame Asante')
            ->assertSee('kwame@example.com');
    }

    /**
     * "Has bought", not "has credits left": spending every credit on the
     * talent pool must not close the door on your own applicants.
     */
    public function test_spending_every_credit_does_not_close_your_own_applicants(): void
    {
        $company = $this->company();
        $opening = $this->job($company);
        $this->buyPlan($company, 1);

        // Burn the balance the way the talent pool does.
        $profile = \App\Models\TalentProfile::create([
            'full_name' => 'Ama Boateng',
            'email' => 'ama@example.com',
            'phone' => '+233240000000',
            'sectors' => ['Information Technology'],
            'cv_path' => 'talent-pool/cv/ama.pdf',
            'cv_name' => 'ama-cv.pdf',
        ]);
        $company->unlockCv($profile);

        $this->assertSame(0, $company->fresh()->creditsLeft());

        $this->actingAs($company->user)
            ->get(route('company.applications', $opening))
            ->assertOk();
    }

    /**
     * A plan buys your own applicants, never someone else's. Wrong company is
     * a 403 and stays a 403 — there is nothing to buy that would make it theirs.
     */
    public function test_a_plan_does_not_open_another_companys_applicants(): void
    {
        $mine = $this->company('Acme Ltd');
        $theirs = $this->company('Rival Ltd');
        $this->buyPlan($mine);

        $opening = $this->job($theirs);
        $application = $this->application($opening);

        $this->actingAs($mine->user)
            ->get(route('company.applications', $opening))
            ->assertForbidden();

        $this->actingAs($mine->user)
            ->get(route('company.applications.cv', $application))
            ->assertForbidden();
    }
}
