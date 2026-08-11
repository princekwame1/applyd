<?php

namespace App\Http\Controllers;

use App\Models\SurveyResponse;
use App\Support\Surveys;
use Illuminate\Http\Request;

/**
 * The public "Pulse Check" — two short check-in surveys participants fill in
 * around a session. No account, no login: the links get handed out in the room.
 */
class SurveyController extends Controller
{
    public function index()
    {
        $surveys = [];

        foreach (Surveys::types() as $type => $copy) {
            $questions = Surveys::questions($type);

            if ($questions->isEmpty()) {
                continue;
            }

            $surveys[$type] = $copy + ['count' => $questions->count()];
        }

        return view('surveys.index', compact('surveys'));
    }

    public function show(string $type)
    {
        abort_unless(Surveys::exists($type), 404);

        $questions = Surveys::questions($type);

        abort_if($questions->isEmpty(), 404);

        return view('surveys.show', [
            'type' => $type,
            'copy' => Surveys::copy($type),
            'questions' => $questions,
        ]);
    }

    public function store(Request $request, string $type)
    {
        abort_unless(Surveys::exists($type), 404);

        $questions = Surveys::questions($type);

        abort_if($questions->isEmpty(), 404);

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
            'survey_type' => $type,
            'answers' => $answers,
        ]);

        return redirect()->route('surveys.thanks')->with('survey_type', $type);
    }

    public function thanks(Request $request)
    {
        $type = $request->session()->get('survey_type');

        // Landing here directly (refresh, bookmark) has nothing to thank anyone
        // for — send them back to the picker.
        if (! $type || ! Surveys::exists($type)) {
            return redirect()->route('surveys.index');
        }

        return view('surveys.thanks', [
            'type' => $type,
            'copy' => Surveys::copy($type),
        ]);
    }
}
