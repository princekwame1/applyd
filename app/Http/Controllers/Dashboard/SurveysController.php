<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\SurveyResponsesExport;
use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use App\Support\Surveys;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Where the check-in answers land: aggregates per question, plus the raw
 * responses underneath.
 */
class SurveysController extends Controller
{
    public function index(Request $request)
    {
        $survey = $this->resolveType($request);

        $responses = SurveyResponse::forSurvey($survey)->get();

        return view('dashboard.surveys.index', [
            'survey' => $survey,
            'copy' => Surveys::copy($survey),
            'summary' => Surveys::summarise($survey, $responses),
            'total' => $responses->count(),
            'today' => $responses->where('created_at', '>=', now()->startOfDay())->count(),
            'counts' => collect(Surveys::types())
                ->map(fn ($copy, $type) => SurveyResponse::forSurvey($type)->count())
                ->all(),
        ]);
    }

    /**
     * A printable sheet to tape to the wall: big QR, short URL, nothing else.
     * Deliberately not on the admin layout — it's meant to come out of a printer.
     */
    public function poster(Request $request)
    {
        $survey = $this->resolveType($request);

        return view('dashboard.surveys.poster', [
            'survey' => $survey,
            'copy' => Surveys::copy($survey),
            'url' => route('surveys.show', $survey),
        ]);
    }

    public function show(SurveyResponse $response)
    {
        return view('dashboard.surveys.partials.response', [
            'response' => $response,
            'questions' => Surveys::questions($response->survey_type),
        ]);
    }

    public function destroy(SurveyResponse $response)
    {
        $survey = $response->survey_type;
        $response->delete();

        return redirect()
            ->route('dashboard.surveys', ['survey' => $survey])
            ->with('status', 'Response deleted.');
    }

    public function export(Request $request)
    {
        $survey = $this->resolveType($request);

        return Excel::download(
            new SurveyResponsesExport($survey),
            'pulse-check-'.$survey.'-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    /** The tab being viewed, falling back to the first configured survey. */
    protected function resolveType(Request $request): string
    {
        $survey = (string) $request->query('survey', '');

        return Surveys::exists($survey)
            ? $survey
            : (string) array_key_first(Surveys::types());
    }
}
