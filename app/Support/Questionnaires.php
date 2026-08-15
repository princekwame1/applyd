<?php

namespace App\Support;

use App\Models\QuestionnaireQuestion;
use Illuminate\Support\Collection;

class Questionnaires
{
    /**
     * Which of a form's questions are actually being asked, given the answers
     * so far. This is the one place that decides it — the public page, the
     * validator and the store all read from here, so what gets shown, what gets
     * required and what gets kept can never drift apart.
     *
     * Questions are walked in display order and a rule may only point at a
     * question that came earlier, so a chain resolves in a single pass: if the
     * controlling question is itself hidden, everything hanging off it is
     * hidden too, regardless of what was posted for it.
     *
     * @param  Collection<int, QuestionnaireQuestion>  $questions
     * @param  array<string, mixed>  $answers
     * @return Collection<int, QuestionnaireQuestion>
     */
    public static function visible(Collection $questions, array $answers): Collection
    {
        $shown = [];

        return $questions->filter(function (QuestionnaireQuestion $question) use (&$shown, $answers) {
            $rule = $question->condition();

            if (! $rule) {
                return $shown[$question->key] = true;
            }

            // A rule naming a question that isn't on the form (or isn't being
            // asked) can never be satisfied — better to drop the question than
            // to ask something whose premise was never established.
            if (empty($shown[$rule['key']])) {
                return false;
            }

            return $shown[$question->key] = $question->conditionMet($answers[$rule['key']] ?? null);
        })->values();
    }

    /**
     * The questions a rule is allowed to point at: everything before this one
     * that holds a plain answer. A file upload has nothing to compare against,
     * and a question can't depend on itself or on one asked later.
     *
     * @param  Collection<int, QuestionnaireQuestion>  $questions
     * @return Collection<int, QuestionnaireQuestion>
     */
    public static function controllersFor(Collection $questions, ?QuestionnaireQuestion $question = null): Collection
    {
        return $questions
            ->reject(fn (QuestionnaireQuestion $q) => $q->isFile())
            ->when($question?->exists, fn (Collection $c) => $c->filter(
                fn (QuestionnaireQuestion $q) => $q->id !== $question->id
                    && [$q->sort_order, $q->id] < [$question->sort_order, $question->id],
            ))
            ->values();
    }
}
