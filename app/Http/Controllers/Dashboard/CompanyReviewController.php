<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\CompaniesExport;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\JobBoardNotificationService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Verifying who is behind a job posting.
 *
 * The screen exists to answer one question — is this employer who they say
 * they are — so the review page leads with the Ghana Card and with any other
 * account already carrying that number.
 */
class CompanyReviewController extends Controller
{
    public function __construct(protected JobBoardNotificationService $notifier) {}

    public function index()
    {
        return view('dashboard.companies.index', [
            'pending' => Company::pending()->count(),
            'approved' => Company::approved()->count(),
            'rejected' => Company::rejected()->count(),
        ]);
    }

    public function show(Company $company)
    {
        $company->load(['user', 'openings' => fn ($q) => $q->latest()]);

        return view('dashboard.companies.show', [
            'company' => $company,
            // Same card on another account. Not an error — one person may run
            // two businesses — but the reviewer has to see it to judge.
            'duplicates' => $company->cardDuplicates()->with('user')->get(),
        ]);
    }

    public function approve(Request $request, Company $company)
    {
        if (! $company->approve($request->user())) {
            return back()->with('status', $company->name.' was already approved.');
        }

        $this->notifier->companyApproved($company);

        return back()->with('status', $company->name.' is verified — they have been emailed.');
    }

    public function reject(Request $request, Company $company)
    {
        $data = $request->validate([
            // Required, and long enough to be a sentence: "no" on its own
            // gives the employer nothing to act on and comes straight back
            // as a support email.
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $company->reject($data['reason'], $request->user());

        $this->notifier->companyRejected($company, $data['reason']);

        return back()->with('status', $company->name.' was rejected and told why.');
    }

    /** Send the registration email again — for an address that bounced. */
    public function resendRegistration(Company $company)
    {
        $sent = $this->notifier->companyRegistered($company);

        return back()->with(
            $sent ? 'status' : 'error',
            $sent ? 'Registration email re-sent.' : 'Could not send — check the contact address on this account.',
        );
    }

    public function export()
    {
        return Excel::download(new CompaniesExport, 'companies-'.now()->format('Y-m-d').'.xlsx');
    }
}
