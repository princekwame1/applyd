<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\JobPostingsExport;
use App\Http\Controllers\Controller;
use App\Models\JobOpening;
use App\Services\JobBoardNotificationService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Reading a posting before it reaches the public board.
 *
 * Separate from company verification on purpose: a verified employer can
 * still post something that shouldn't run, and a posting from an unverified
 * one may be perfectly fine and simply waiting on the other queue.
 */
class JobPostingReviewController extends Controller
{
    public function __construct(protected JobBoardNotificationService $notifier) {}

    public function index()
    {
        return view('dashboard.job-postings.index', [
            'pending' => JobOpening::pending()->count(),
            'approved' => JobOpening::approved()->count(),
            'rejected' => JobOpening::rejected()->count(),
            // Approved, but held off the board by the company's own status.
            // Without this number the queue looks clear while nothing is live.
            'heldByCompany' => JobOpening::approved()
                ->whereHas('company', fn ($q) => $q->where('status', '!=', JobOpening::APPROVED))
                ->count(),
        ]);
    }

    public function show(JobOpening $opening)
    {
        $opening->load(['company.user']);

        return view('dashboard.job-postings.show', ['opening' => $opening]);
    }

    public function approve(Request $request, JobOpening $opening)
    {
        if (! $opening->approve($request->user())) {
            return back()->with('status', 'That posting was already approved.');
        }

        $this->notifier->jobApproved($opening);

        $note = $opening->company?->isApproved()
            ? ' It is on the board now.'
            : ' It stays off the board until '.$opening->company?->name.' is verified.';

        return back()->with('status', 'Posting approved — the employer has been emailed.'.$note);
    }

    public function reject(Request $request, JobOpening $opening)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $opening->reject($data['reason'], $request->user());

        $this->notifier->jobRejected($opening, $data['reason']);

        return back()->with('status', 'Posting rejected and the employer told why.');
    }

    public function export()
    {
        return Excel::download(new JobPostingsExport, 'job-postings-'.now()->format('Y-m-d').'.xlsx');
    }
}
