<?php

namespace App\Http\Controllers;

use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The public side of a questionnaire: one shareable link, no account needed.
 *
 * A form the admin hasn't published is a draft and 404s. A published form that
 * is outside its window or over its response cap is a different thing — the
 * link was handed out and people will click it — so that gets a plain "this is
 * closed" page rather than a dead end.
 */
class QuestionnaireFormController extends Controller
{
    public function show(Questionnaire $questionnaire)
    {
        $questions = $this->publishedQuestions($questionnaire);

        if ($reason = $questionnaire->closedReason()) {
            return response()->view('forms.closed', [
                'questionnaire' => $questionnaire,
                'reason' => $reason,
            ], 200);
        }

        return view('forms.show', [
            'questionnaire' => $questionnaire,
            'questions' => $questions,
        ]);
    }

    public function store(Request $request, Questionnaire $questionnaire)
    {
        $questions = $this->publishedQuestions($questionnaire);

        // Re-checked on the way in, not just when the page was rendered: the
        // window can lapse or the cap fill while someone has the form open.
        if ($reason = $questionnaire->closedReason()) {
            return redirect()->route('forms.show', $questionnaire)->with('form_closed', $reason);
        }

        $rules = [];
        $attributes = [];

        foreach ($questions as $question) {
            $rules += $question->validationRules();
            $attributes[$question->inputPath()] = '"'.$question->label.'"';
        }

        $validated = $request->validate($rules, [], $attributes);

        $answers = [];

        foreach ($questions as $question) {
            if ($question->isFile()) {
                continue;   // handled below, once the response row exists
            }

            $value = data_get($validated, $question->inputPath());

            if ($value === null || $value === '' || $value === []) {
                continue;   // skipped optional question — leave the key out entirely
            }

            $answers[$question->key] = is_array($value)
                ? array_values(array_map(fn ($v) => trim((string) $v), $value))
                : trim((string) $value);
        }

        // The uploads and the row they belong to land together or not at all.
        $response = DB::transaction(function () use ($request, $questionnaire, $questions, $answers) {
            $response = QuestionnaireResponse::create([
                'questionnaire_id' => $questionnaire->id,
                'answers' => $answers,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);

            foreach ($questions as $question) {
                if (! $question->isFile()) {
                    continue;
                }

                $file = $request->file($question->inputPath());

                if (! $file) {
                    continue;   // optional upload, left blank
                }

                $response->files()->create([
                    'question_key' => $question->key,
                    // Private disk: an upload is only ever served back through
                    // the authorised dashboard download route.
                    'path' => $file->store('questionnaires/'.$questionnaire->id, 'local'),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);

                // Mirrored into the answers map so the dashboard table, the
                // detail view and the export all read one shape.
                $answers[$question->key] = $file->getClientOriginalName();
            }

            $response->update(['answers' => $answers]);

            return $response;
        });

        return redirect()
            ->route('forms.thanks', $questionnaire)
            ->with('questionnaire_reference', $response->reference);
    }

    public function thanks(Request $request, Questionnaire $questionnaire)
    {
        $reference = $request->session()->get('questionnaire_reference');

        // Landing here directly (refresh, bookmark) has nothing to confirm.
        if (! $reference) {
            return redirect()->route('forms.show', $questionnaire);
        }

        return view('forms.thanks', [
            'questionnaire' => $questionnaire,
            'reference' => $reference,
        ]);
    }

    /**
     * The questions a published form asks. An unpublished form, or one with
     * nothing to ask, isn't a public page at all.
     *
     * @return Collection<int, QuestionnaireQuestion>
     */
    private function publishedQuestions(Questionnaire $questionnaire): Collection
    {
        abort_unless($questionnaire->is_published, 404);

        $questions = $questionnaire->liveQuestions();

        abort_if($questions->isEmpty(), 404);

        return $questions;
    }
}
