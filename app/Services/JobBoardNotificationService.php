<?php

namespace App\Services;

use App\Models\Company;
use App\Models\JobOpening;
use Illuminate\Support\Facades\Log;

/**
 * The six emails the review workflow sends.
 *
 * One place, because the token sets are the interesting part: a recruiter is
 * addressed by the contact person's name, not the company's, and a rejection
 * is worthless without the reason attached. Every send goes through
 * EmailNotificationService, so each one is logged, resendable from Email
 * Delivery, and swallows its own failures — an SMTP outage must never undo an
 * approval an admin has already made, or a registration already accepted.
 */
class JobBoardNotificationService
{
    public function __construct(protected EmailNotificationService $emails) {}

    /** Straight after a company signs up: we have it, we are checking it. */
    public function companyRegistered(Company $company): bool
    {
        return $this->send('company_registered', $company, $this->companyVariables($company));
    }

    /** Identity confirmed — their postings can go live from here. */
    public function companyApproved(Company $company): bool
    {
        return $this->send('company_approved', $company, $this->companyVariables($company));
    }

    /** Declined, with what they would have to fix. */
    public function companyRejected(Company $company, string $reason): bool
    {
        return $this->send('company_rejected', $company, array_merge(
            $this->companyVariables($company),
            ['reason' => $reason],
        ));
    }

    public function jobSubmitted(JobOpening $opening): bool
    {
        return $this->send('job_submitted', $opening->company, $this->jobVariables($opening));
    }

    public function jobApproved(JobOpening $opening): bool
    {
        return $this->send('job_approved', $opening->company, $this->jobVariables($opening));
    }

    public function jobRejected(JobOpening $opening, string $reason): bool
    {
        return $this->send('job_rejected', $opening->company, array_merge(
            $this->jobVariables($opening),
            ['reason' => $reason],
        ));
    }

    /**
     * Everything lands on the contact person's own address — the account
     * login. A company row with no user behind it can't be written to, so it
     * is skipped rather than allowed to throw inside an approval.
     */
    protected function send(string $key, ?Company $company, array $variables): bool
    {
        $user = $company?->user;

        if (! $user || ! $user->email) {
            Log::warning('Job board email skipped: no contact address', [
                'template' => $key,
                'company_id' => $company?->id,
            ]);

            return false;
        }

        return $this->emails->sendTemplate($key, $user->email, $variables, null, $user->name);
    }

    /** @return array<string, string> */
    protected function companyVariables(Company $company): array
    {
        $contact = trim((string) $company->user?->name);

        return [
            'first_name' => explode(' ', $contact)[0] ?: $contact,
            'contact_name' => $contact,
            'company_name' => (string) $company->name,
            'ghana_card' => (string) $company->ghana_card,
            'portal_url' => route('company.home'),
            'site_name' => (string) config('app.name'),
            'site_url' => (string) config('app.url'),
        ];
    }

    /** @return array<string, string> */
    protected function jobVariables(JobOpening $opening): array
    {
        return array_merge($this->companyVariables($opening->company), [
            'job_title' => (string) $opening->title,
            'job_url' => route('jobs.show', $opening),
        ]);
    }
}
