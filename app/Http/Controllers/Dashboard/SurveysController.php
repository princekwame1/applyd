<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\SurveyResponsesExport;
use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Support\Surveys;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Where the check-in answers land: aggregates per question, plus the raw
 * responses underneath. One survey at a time, picked by the tab bar.
 */
class SurveysController extends Controller
{
    public function index(Request $request)
    {
        $survey = $this->resolveSurvey($request);

        if (! $survey) {
            return view('dashboard.surveys.index', ['survey' => null]);
        }

        $responses = SurveyResponse::forSurvey($survey)->get();

        return view('dashboard.surveys.index', [
            'survey' => $survey,
            'surveys' => Surveys::all()->loadCount('responses'),
            'summary' => Surveys::summarise($survey, $responses),
            'total' => $responses->count(),
            'today' => $responses->where('created_at', '>=', now()->startOfDay())->count(),
        ]);
    }

    /**
     * A printable sheet to tape to the wall: big QR, short URL, nothing else.
     * Deliberately not on the admin layout — it's meant to come out of a printer.
     */
    public function poster(Request $request)
    {
        $survey = $this->resolveSurvey($request);

        abort_unless($survey, 404);

        return view('dashboard.surveys.poster', [
            'survey' => $survey,
            'url' => route('surveys.show', $survey),
        ]);
    }

    public function show(SurveyResponse $response)
    {
        return view('dashboard.surveys.partials.response', [
            'response' => $response->load('survey'),
            'questions' => $response->survey?->liveQuestions() ?? collect(),
        ]);
    }

    public function destroy(SurveyResponse $response)
    {
        $slug = $response->survey?->slug;
        $response->delete();

        return redirect()
            ->route('dashboard.surveys', ['survey' => $slug])
            ->with('status', 'Response deleted.');
    }

    public function export(Request $request)
    {
        $survey = $this->resolveSurvey($request);

        abort_unless($survey, 404);

        return Excel::download(
            new SurveyResponsesExport($survey),
            'pulse-check-'.$survey->slug.'-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /** The tab being viewed, falling back to the first survey. */
    protected function resolveSurvey(Request $request): ?Survey
    {
        $slug = (string) $request->query('survey', '');

        return Survey::where('slug', $slug)->first()
            ?? Survey::ordered()->first();
    }
}
