<?php

namespace App\Http\Controllers;

use App\Models\JobOpening;
use App\Services\JobBoardNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;

        return view('company.index', [
            'company' => $company,
            'openings' => $company->openings()->withCount('applications')->latest()->get(),
            'verification' => $company->status,
            // Drives the padlock on the applications count. The count itself
            // stays visible either way — hiding it would hide the reason to buy.
            'hasPlan' => $company->hasPlan(),
        ]);
    }

    /**
     * Posting is open to everyone, verified or not — waiting on a review to
     * even write the advert would cost the recruiter a day for nothing. What
     * the review gates is the public board, not the portal.
     */
    public function store(Request $request, JobBoardNotificationService $notifier)
    {
        $opening = $request->user()->company->openings()->create(
            $this->validated($request) + ['status' => JobOpening::PENDING]
        );

        $notifier->jobSubmitted($opening);

        return redirect()->route('company.home')->with(
            'status',
            'Job posted — it goes on the public board once we have read it. We will email you when it is live.',
        );
    }

    public function edit(Request $request, JobOpening $opening)
    {
        $this->authorizeOpening($request, $opening);

        return view('company.edit-job', compact('opening'));
    }

    public function update(Request $request, JobOpening $opening)
    {
        $this->authorizeOpening($request, $opening);

        $data = $this->validated($request);
        $data['is_open'] = $request->boolean('is_open');

        $opening->update($data);

        // An approved posting that has been rewritten is not the posting that
        // was approved — otherwise the review is a formality anyone can walk
        // round by editing afterwards. Opening and closing it is the recruiter's
        // own switch and doesn't count as a rewrite.
        $rewritten = $opening->wasChanged(['title', 'description', 'location', 'type', 'sector', 'salary_range']);

        if ($rewritten && $opening->isApproved()) {
            $opening->sendBackForReview();

            return redirect()->route('company.home')->with(
                'status',
                'Posting updated. Because the wording changed it goes back for a quick read before it returns to the board.',
            );
        }

        return redirect()->route('company.home')->with('status', 'Job opening updated.');
    }

    public function destroy(Request $request, JobOpening $opening)
    {
        $this->authorizeOpening($request, $opening);

        $opening->delete();

        return redirect()->route('company.home')->with('status', 'Job opening deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:20000'],
            'location' => ['nullable', 'string', 'max:150'],
            'type' => ['required', Rule::in(JobOpening::TYPES)],
            'sector' => ['nullable', Rule::in(JobOpening::SECTORS)],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $data['description'] = \App\Support\Html::clean($data['description']);

        return $data;
    }

    private function authorizeOpening(Request $request, JobOpening $opening): void
    {
        abort_unless($opening->company_id === $request->user()->company->id, 403);
    }
}
