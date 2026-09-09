<?php

namespace App\Http\Controllers;

use App\Models\ApplicationDocument;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Support\CvText;
use App\Support\FitScore;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyApplicationController extends Controller
{
    /**
     * What may be handed back to the browser to render in place. Deliberately
     * short: these are files a stranger uploaded, and anything the browser
     * would execute as a document in our own origin — HTML, SVG — is not on
     * it. A Word file is shown as extracted text instead, never streamed.
     */
    private const INLINE_TYPES = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    public function index(Request $request, JobOpening $opening)
    {
        $this->authorizeOpening($request, $opening);

        $applications = $opening->applications()->with('documents');

        // Best fit first, then newest. `fit_score IS NULL` sorts the unscored
        // to the bottom on MySQL and SQLite alike — they are not zeroes, and
        // must not sit below someone who genuinely matched nothing.
        $applications->orderByRaw('fit_score IS NULL')
            ->orderByDesc('fit_score')
            ->latest();

        match ($request->input('fit')) {
            'top' => $applications->where('fit_score', '>=', FitScore::TOP),
            'missing' => $applications->where('meets_requirements', false),
            'meets' => $applications->where('meets_requirements', true),
            default => null,
        };

        if (in_array($request->input('status'), JobApplication::STATUSES, true)) {
            $applications->where('status', $request->input('status'));
        }

        return view('company.applications', [
            'opening' => $opening,
            'applications' => $applications->get(),
            'criteria' => $opening->screeningCriteria()->get(),
            'total' => $opening->applications()->count(),
            'topCount' => $opening->applications()->where('fit_score', '>=', FitScore::TOP)->count(),
            'missingCount' => $opening->applications()->where('meets_requirements', false)->count(),
            'fit' => $request->input('fit'),
            'status' => $request->input('status'),
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

    /**
     * The same file, rendered in place instead of landing in a downloads
     * folder. It goes through the identical two gates as the download — the
     * viewer is a second door to the same private file, not a way round it.
     */
    public function previewCv(Request $request, JobApplication $application)
    {
        $this->authorizeApplication($request, $application);

        return $this->preview($application->cv_path, $application->cv_name, $application->cv_text);
    }

    public function previewDocument(Request $request, ApplicationDocument $document)
    {
        $this->authorizeApplication($request, $document->application);

        return $this->preview($document->path, $document->original_name);
    }

    /**
     * PDFs and images are streamed for the browser's own viewer; a Word file
     * has no browser viewer, so it is read into text on the spot. Whatever
     * cannot be shown says so and offers the download rather than handing
     * over a blank frame.
     */
    private function preview(string $path, string $name, ?string $text = null): StreamedResponse|\Illuminate\Http\Response
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        abort_unless(Storage::disk('local')->exists($path), 404);

        if ($type = self::INLINE_TYPES[$extension] ?? null) {
            return Storage::disk('local')->response($path, $name, [
                'Content-Type' => $type,
                // The type we send is the type it is read as. Without this a
                // browser may sniff its own, which is how an "image" gets
                // treated as something that runs.
                'X-Content-Type-Options' => 'nosniff',
                'Content-Disposition' => 'inline; filename="'.addslashes($name).'"',
            ]);
        }

        return response()->view('company.partials.text-preview', [
            'name' => $name,
            'text' => $text ?? rescue(fn () => CvText::fromStorage($path), null, false),
        ]);
    }

    /**
     * Two gates, and every action in this controller goes through here —
     * including the downloads and the viewer, which is the point: a paywall
     * that only covers the list is decoration when the CV sits one guessable
     * id away at /company/applications/{id}/cv.
     */
    private function authorizeOpening(Request $request, JobOpening $opening): void
    {
        $company = $request->user()->company;

        // Whose job it is. Wrong company is a 403, not a redirect — there is
        // nothing to buy that would make it theirs.
        abort_unless($opening->company_id === $company->id, 403);

        // Whether they are on a plan. Posting a job is free and stays free;
        // reviewing who applied is what a plan buys. Sent to the plans page
        // rather than refused, because buying one is the way out.
        if (! $company->hasPlan()) {
            throw new HttpResponseException(
                redirect()->route('company.plans')->with(
                    'error',
                    'Reviewing applicants needs a plan. Choose one below to open the people who applied to your jobs.',
                )
            );
        }
    }

    private function authorizeApplication(Request $request, JobApplication $application): void
    {
        $this->authorizeOpening($request, $application->opening);
    }
}
