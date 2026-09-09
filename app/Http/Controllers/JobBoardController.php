<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDocument;
use App\Models\JobOpening;
use App\Support\CvText;
use App\Support\FitScore;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobBoardController extends Controller
{
    public function index(Request $request)
    {
        $query = JobOpening::open()->with('company');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('company', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('sector')) {
            $query->where('sector', $request->input('sector'));
        }

        if ($request->filled('location')) {
            $query->where('location', $request->input('location'));
        }

        $openings = $query->latest()->paginate(6);

        return view('jobs.index', [
            'openings' => $openings,
            'types' => JobOpening::TYPES,
            'sectors' => JobOpening::open()->select('sector')->distinct()->whereNotNull('sector')->orderBy('sector')->pluck('sector'),
            'locations' => JobOpening::open()->select('location')->distinct()->whereNotNull('location')->pluck('location'),
            'search' => $request->input('search'),
            'selectedType' => $request->input('type'),
            'selectedSector' => $request->input('sector'),
            'selectedLocation' => $request->input('location'),
        ]);
    }

    /**
     * A posting nobody has approved is not public, and neither is one from a
     * company we have not identified — the index scope already knows that, so
     * the detail page has to as well or the advert is one guessed id away.
     * 404, not 403: to the outside world it simply isn't there.
     */
    public function show(JobOpening $opening)
    {
        $opening->load('company', 'screeningQuestions');

        abort_unless($opening->isApproved() && $opening->company?->isApproved(), 404);

        return view('jobs.show', compact('opening'));
    }

    public function apply(Request $request, JobOpening $opening)
    {
        // is_live, not is_accepting: both approvals as well as the recruiter's
        // own switch and deadline. An unapproved advert must not collect CVs.
        abort_unless($opening->is_live, 404);

        // The screening questions this role asks, if any. Rules are built from
        // the rows, so the form can never accept an answer its recruiter never
        // offered — and a question added tomorrow needs no code today.
        $questions = $opening->screeningQuestions()->get();

        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('job_applications')->where('job_opening_id', $opening->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:4096'],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:4096'],
        ];

        $names = [];

        foreach ($questions as $question) {
            $rules['screening.'.$question->key] = $question->rules();
            $names['screening.'.$question->key] = strtolower($question->label);
        }

        $data = $request->validate($rules, [
            'email.unique' => 'You have already applied to this job with this email address.',
            'documents.max' => 'You can upload at most 5 supporting documents.',
        ], $names);

        $cv = $request->file('cv');
        $cvPath = $cv->store('applications/'.$opening->id.'/cv', 'local');

        $application = $opening->applications()->create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'cover_letter' => $data['cover_letter'] ?? null,
            // Only the keys that were actually asked: anything else posted
            // alongside them is dropped rather than filed under a question
            // this job does not have.
            'screening_answers' => collect($data['screening'] ?? [])
                ->only($questions->pluck('key')->all())
                ->all(),
            'cv_path' => $cvPath,
            'cv_name' => $cv->getClientOriginalName(),
            // Best-effort, and null when the file will not give up its text.
            // Reading it must never cost the candidate their application, so
            // a parser that throws is caught here and the CV is simply
            // unscored — see App\Support\CvText.
            'cv_text' => rescue(fn () => CvText::fromStorage($cvPath), null, false),
        ]);

        // Scored on the way in, so the recruiter's list is ordered the moment
        // they open it rather than on a job somebody has to remember to run.
        FitScore::store($application, $opening->screeningCriteria()->get());

        foreach ($request->file('documents', []) as $document) {
            $application->documents()->create([
                'path' => $document->store('applications/'.$opening->id.'/documents', 'local'),
                'original_name' => $document->getClientOriginalName(),
            ]);
        }

        return redirect()
            ->route('jobs.show', $opening)
            ->with('applied', 'Your application has been submitted. '.$opening->company->name.' will be in touch if you are shortlisted.');
    }
}
