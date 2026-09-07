<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmailLog;
use App\Models\JobOpening;
use App\Models\User;
use App\Support\GhanaCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Job poster identification and posting approval.
 *
 * The rule the whole feature turns on: verification gates the PUBLIC BOARD,
 * never the recruiter's own portal. They register, sign in and post the same
 * day; what waits is publication.
 */
class JobBoardVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function company(string $status = Company::APPROVED, string $name = 'Acme Ltd'): Company
    {
        $user = User::factory()->create();
        $user->assignRole('company');

        return Company::create([
            'user_id' => $user->id,
            'name' => $name,
            'ghana_card' => 'GHA-123456789-0',
            'status' => $status,
        ]);
    }

    protected function job(Company $company, string $status = JobOpening::APPROVED): JobOpening
    {
        return $company->openings()->create([
            'title' => 'Developer',
            'description' => 'Build things.',
            'type' => 'Full-time',
            'sector' => 'Information Technology',
            'is_open' => true,
            'status' => $status,
        ]);
    }

    // ------------------------------------------------------------ the card itself

    public function test_the_ghana_card_is_stored_in_one_shape_however_it_is_typed(): void
    {
        $this->assertSame('GHA-123456789-0', GhanaCard::normalise('gha 123456789 0'));
        $this->assertSame('GHA-123456789-0', GhanaCard::normalise('1234567890'));
        $this->assertSame('GHA-123456789-0', GhanaCard::normalise('GHA-123456789-0'));

        // Not a card number at all.
        $this->assertNull(GhanaCard::normalise('12345'));
        $this->assertNull(GhanaCard::normalise(''));
        $this->assertNull(GhanaCard::normalise('GHA-12345678901-0'));
    }

    public function test_registration_demands_a_readable_card_number(): void
    {
        $this->post(route('companies.register.store'), [
            'company_name' => 'Nope Ltd',
            'ghana_card' => 'not-a-card',
            'contact_name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('ghana_card');

        $this->assertSame(0, Company::count());
    }

    // ------------------------------------------------------------- registration

    public function test_registering_lands_pending_and_sends_the_registration_email(): void
    {
        Mail::fake();

        $this->post(route('companies.register.store'), [
            'company_name' => 'Acme Ltd',
            'ghana_card' => 'gha 123456789 0',
            'contact_name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('company.home'));

        $company = Company::first();

        $this->assertTrue($company->isPending());
        $this->assertSame('GHA-123456789-0', $company->ghana_card);

        $log = EmailLog::where('template_key', 'company_registered')->first();
        $this->assertNotNull($log, 'The registration email should be logged.');
        $this->assertSame('ama@example.com', $log->email);
    }

    /** The whole point of "allow them to continue without verification". */
    public function test_an_unverified_company_can_sign_in_and_post_a_job(): void
    {
        $company = $this->company(Company::PENDING);

        $this->actingAs($company->user)->get(route('company.home'))->assertOk();

        $this->actingAs($company->user)->post(route('company.jobs.store'), [
            'title' => 'Data Analyst',
            'description' => 'Crunch numbers.',
            'type' => 'Full-time',
            'sector' => 'Information Technology',
        ])->assertRedirect(route('company.home'));

        $opening = $company->openings()->first();
        $this->assertNotNull($opening);
        $this->assertTrue($opening->isPending(), 'A new posting waits for review.');

        $this->assertNotNull(EmailLog::where('template_key', 'job_submitted')->first());
    }

    // ------------------------------------------------------- what the public sees

    public function test_the_board_shows_only_approved_jobs_from_verified_companies(): void
    {
        $live = $this->job($this->company(Company::APPROVED, 'Verified Ltd'));
        $unreviewedJob = $this->job($this->company(Company::APPROVED, 'Verified Two'), JobOpening::PENDING);
        $unverifiedPoster = $this->job($this->company(Company::PENDING, 'Unverified Ltd'));

        $response = $this->get(route('jobs'))->assertOk();

        $response->assertSee($live->company->name);
        $response->assertDontSee($unreviewedJob->company->name);
        $response->assertDontSee($unverifiedPoster->company->name);

        $this->assertSame([$live->id], JobOpening::open()->pluck('id')->all());
    }

    /**
     * The detail page has to enforce it too, or the advert is one guessed id
     * away from being public regardless of the listing.
     */
    public function test_an_unapproved_posting_is_a_404_not_just_hidden_from_the_list(): void
    {
        $pendingJob = $this->job($this->company(), JobOpening::PENDING);
        $unverifiedPoster = $this->job($this->company(Company::PENDING, 'Unverified Ltd'));

        $this->get(route('jobs.show', $pendingJob))->assertNotFound();
        $this->get(route('jobs.show', $unverifiedPoster))->assertNotFound();
    }

    public function test_nobody_can_apply_to_a_posting_that_is_not_live(): void
    {
        $opening = $this->job($this->company(), JobOpening::PENDING);

        $this->post(route('jobs.apply', $opening), [
            'full_name' => 'Kwame Asante',
            'email' => 'kwame@example.com',
        ])->assertNotFound();

        $this->assertSame(0, $opening->applications()->count());
    }

    // ------------------------------------------------------------- the decisions

    public function test_approving_a_company_emails_them_and_publishes_their_approved_jobs(): void
    {
        $company = $this->company(Company::PENDING);
        $opening = $this->job($company, JobOpening::APPROVED);

        $this->assertFalse($opening->fresh()->is_live, 'Held while the poster is unverified.');

        $this->actingAs($this->admin())
            ->post(route('dashboard.companies.approve', $company))
            ->assertRedirect();

        $this->assertTrue($company->fresh()->isApproved());
        $this->assertTrue($opening->fresh()->is_live, 'Verifying the poster releases what was already approved.');
        $this->assertNotNull(EmailLog::where('template_key', 'company_approved')->first());
    }

    public function test_rejecting_carries_the_reason_into_the_email(): void
    {
        $company = $this->company(Company::PENDING);
        $reason = 'The Ghana Card number does not match the contact name you gave.';

        $this->actingAs($this->admin())
            ->post(route('dashboard.companies.reject', $company), ['reason' => $reason])
            ->assertRedirect();

        $company->refresh();
        $this->assertTrue($company->isRejected());
        $this->assertSame($reason, $company->review_note);

        $log = EmailLog::where('template_key', 'company_rejected')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString($reason, $log->body);
    }

    /** A rejection with no explanation is a support call, so it is refused. */
    public function test_a_rejection_must_carry_a_reason(): void
    {
        $company = $this->company(Company::PENDING);

        $this->actingAs($this->admin())
            ->post(route('dashboard.companies.reject', $company), ['reason' => 'no'])
            ->assertSessionHasErrors('reason');

        $this->assertTrue($company->fresh()->isPending());
    }

    public function test_approving_a_posting_emails_the_employer_with_the_link(): void
    {
        $company = $this->company();
        $opening = $this->job($company, JobOpening::PENDING);

        $this->actingAs($this->admin())
            ->post(route('dashboard.job-postings.approve', $opening))
            ->assertRedirect();

        $this->assertTrue($opening->fresh()->is_live);

        $log = EmailLog::where('template_key', 'job_approved')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString(route('jobs.show', $opening), $log->cta_url.$log->body);
    }

    /** Approving twice must not send a second email. */
    public function test_a_repeated_approval_sends_nothing(): void
    {
        $company = $this->company(Company::PENDING);

        $this->actingAs($this->admin())->post(route('dashboard.companies.approve', $company));
        $this->actingAs($this->admin())->post(route('dashboard.companies.approve', $company));

        $this->assertSame(1, EmailLog::where('template_key', 'company_approved')->count());
    }

    // ----------------------------------------------------------- editing after

    public function test_rewriting_an_approved_posting_sends_it_back_for_review(): void
    {
        $company = $this->company();
        $opening = $this->job($company, JobOpening::APPROVED);

        $this->actingAs($company->user)->put(route('company.jobs.update', $opening), [
            'title' => 'Something completely different',
            'description' => 'Rewritten after approval.',
            'type' => 'Full-time',
            'sector' => 'Information Technology',
            'is_open' => '1',
        ])->assertRedirect(route('company.home'));

        $this->assertTrue($opening->fresh()->isPending(), 'A rewritten advert is not the one that was approved.');
    }

    /** Closing a job is the recruiter's own switch, not a rewrite. */
    public function test_merely_closing_a_posting_does_not_send_it_back(): void
    {
        $company = $this->company();
        $opening = $this->job($company, JobOpening::APPROVED);

        $this->actingAs($company->user)->put(route('company.jobs.update', $opening), [
            'title' => $opening->title,
            'description' => $opening->description,
            'type' => $opening->type,
            'sector' => $opening->sector,
        ])->assertRedirect(route('company.home'));

        $opening->refresh();
        $this->assertTrue($opening->isApproved());
        $this->assertFalse($opening->is_open);
    }

    // -------------------------------------------------------------- admin screens

    public function test_the_review_screens_are_admin_only(): void
    {
        $company = $this->company(Company::PENDING);

        $this->actingAs($company->user)->get(route('dashboard.companies'))->assertForbidden();
        $this->actingAs($company->user)->post(route('dashboard.companies.approve', $company))->assertForbidden();

        $this->assertTrue($company->fresh()->isPending());
    }

    public function test_the_review_page_flags_a_card_already_on_another_account(): void
    {
        $first = $this->company(Company::APPROVED, 'First Ltd');
        $second = $this->company(Company::PENDING, 'Second Ltd');

        $this->assertSame($first->ghana_card, $second->ghana_card);

        $this->actingAs($this->admin())
            ->get(route('dashboard.companies.show', $second))
            ->assertOk()
            ->assertSee('First Ltd');
    }
}
