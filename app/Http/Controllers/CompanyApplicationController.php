<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDocument;
use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CompanyApplicationController extends Controller
{
    public function index(Request $request, JobOpening $opening)
    {
        $this->authorizeOpening($request, $opening);

        return view('company.applications', [
            'opening' => $opening,
            'applications' => $opening->applications()->with('documents')->latest()->get(),
        ]);
    }

    public function updateStatus(Request $request, JobApplication $application)
    {
        $this->authorizeApplication($request, $application);

        $data = $request->validate([
            'status' => ['required', Rule::in(JobApplication::STATUSES)],
        ]);

        $application->update($data);

        return back()->with('status', 'Application marked as '.$data['status'].'.');
    }

    public function downloadCv(Request $request, JobApplication $application)
    {
        $this->authorizeApplication($request, $application);

        return Storage::disk('local')->download($application->cv_path, $application->cv_name);
    }

    public function downloadDocument(Request $request, ApplicationDocument $document)
    {
        $this->authorizeApplication($request, $document->application);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    private function authorizeOpening(Request $request, JobOpening $opening): void
    {
        abort_unless($opening->company_id === $request->user()->company->id, 403);
    }

    private function authorizeApplication(Request $request, JobApplication $application): void
    {
        $this->authorizeOpening($request, $application->opening);
    }
}
