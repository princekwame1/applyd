<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;

/**
 * The public "Pulse Check" — short check-in surveys participants fill in around
 * a session. No account, no login: the links get handed out in the room.
 *
 * Which surveys exist is up to the admin (`surveys` table); this only ever
 * shows the ones that are switched on and have something to ask.
 */
class SurveyController extends Controller
{
    public function index()
    {
        $surveys = Survey::active()
            ->ordered()
            ->withCount(['questions as live_questions_count' => fn ($q) => $q->where('active', true)])
            ->get()
            ->filter(fn (Survey $survey) => $survey->live_questions_count > 0);

        return view('surveys.index', compact('surveys'));
    }

    public function show(Survey $survey)
    {
        $questions = $this->openQuestions($survey);

        return view('surveys.show', [
            'survey' => $survey,
            'questions' => $questions,
        ]);
    }

    public function store(Request $request, Survey $survey)
    {
        $questions = $this->openQuestions($survey);

        $rules = [];
        $attributes = [];

        foreach ($questions as $question) {
            $rules['answers.'.$question->key] = $question->rules();
            $attributes['answers.'.$question->key] = 'answer';
        }

        $validated = $request->validate($rules, [], $attributes);

        $answers = [];

        foreach ($questions as $question) {
            $value = $validated['answers'][$question->key] ?? null;

            if ($value === null || $value === '') {
                continue;   // skipped optional question — leave the key out entirely
            }

            $answers[$question->key] = $question->type === 'scale' ? (int) $value : trim((string) $value);
        }

        SurveyResponse::create([
            'survey_id' => $survey->id,
            'answers' => $answers,
        ]);

        return redirect()->route('surveys.thanks')->with('survey_id', $survey->id);
    }

    public function thanks(Request $request)
    {
        $survey = Survey::find($request->session()->get('survey_id'));

        // Landing here directly (refresh, bookmark) has nothing to thank anyone
        // for — send them back to the picker.
        if (! $survey) {
            return redirect()->route('surveys.index');
        }

        return view('surveys.thanks', compact('survey'));
    }

    /**
     * A survey the public can actually fill in: switched on, and with live
     * questions. Anything else is a 404 rather than an empty form.
     */
    private function openQuestions(Survey $survey)
    {
        abort_unless($survey->is_active, 404);

        $questions = $survey->liveQuestions();

        abort_if($questions->isEmpty(), 404);

        return $questions;
    }
}
