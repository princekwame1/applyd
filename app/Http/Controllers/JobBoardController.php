<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDocument;
use App\Models\JobOpening;
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

        if ($request->filled('location')) {
            $query->where('location', $request->input('location'));
        }

        $openings = $query->latest()->paginate(6);

        return view('jobs.index', [
            'openings' => $openings,
            'types' => JobOpening::TYPES,
            'locations' => JobOpening::open()->select('location')->distinct()->whereNotNull('location')->pluck('location'),
            'search' => $request->input('search'),
            'selectedType' => $request->input('type'),
            'selectedLocation' => $request->input('location'),
        ]);
    }

    public function show(JobOpening $opening)
    {
        $opening->load('company');

        return view('jobs.show', compact('opening'));
    }

    public function apply(Request $request, JobOpening $opening)
    {
        abort_unless($opening->is_accepting, 404);

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('job_applications')->where('job_opening_id', $opening->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:4096'],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:4096'],
        ], [
            'email.unique' => 'You have already applied to this job with this email address.',
            'documents.max' => 'You can upload at most 5 supporting documents.',
        ]);

        $cv = $request->file('cv');

        $application = $opening->applications()->create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'cover_letter' => $data['cover_letter'] ?? null,
            'cv_path' => $cv->store('applications/'.$opening->id.'/cv', 'local'),
            'cv_name' => $cv->getClientOriginalName(),
        ]);

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
