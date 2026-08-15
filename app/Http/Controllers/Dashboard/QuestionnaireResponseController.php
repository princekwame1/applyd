<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\QuestionnaireResponsesExport;
use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\QuestionnaireFile;
use App\Models\QuestionnaireResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Where the submissions land: a count, the raw table, one detail view per
 * response, and the spreadsheet.
 */
class QuestionnaireResponseController extends Controller
{
    public function index(Questionnaire $questionnaire)
    {
        return view('dashboard.questionnaires.responses', [
            'questionnaire' => $questionnaire,
            'total' => $questionnaire->responses()->count(),
            'today' => $questionnaire->responses()->where('created_at', '>=', now()->startOfDay())->count(),
            'liveCount' => $questionnaire->questions()->active()->count(),
        ]);
    }

    public function show(QuestionnaireResponse $response)
    {
        return view('dashboard.questionnaires.partials.response', [
            'response' => $response->load(['questionnaire', 'files']),
            'questions' => $response->questionnaire?->questions()->ordered()->get() ?? collect(),
        ]);
    }

    public function destroy(QuestionnaireResponse $response)
    {
        $questionnaire = $response->questionnaire;

        // The rows cascade; the files on disk don't, so they go first.
        foreach ($response->files as $file) {
            Storage::disk('local')->delete($file->path);
        }

        $response->delete();

        return redirect()
            ->route('dashboard.questionnaires.responses', $questionnaire)
            ->with('status', 'Response deleted.');
    }

    public function export(Questionnaire $questionnaire)
    {
        return Excel::download(
            new QuestionnaireResponsesExport($questionnaire),
            'form-'.$questionnaire->slug.'-responses-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /** Uploads live on the private disk — this route is the only way to one. */
    public function download(QuestionnaireFile $file)
    {
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }
}
