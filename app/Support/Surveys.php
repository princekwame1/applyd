<?php

namespace App\Support;

use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Support\Collection;

/**
 * Aggregation behind the dashboard results screen.
 *
 * Surveys and their questions are both DB-driven (`surveys`, `survey_questions`)
 * and admin-editable; config/surveys.php is only the migration seed.
 */
class Surveys
{
    /** Every survey, in display order. */
    public static function all(): Collection
    {
        return Survey::ordered()->get();
    }

    /**
     * Roll responses up per question for the results screen.
     *
     * Counting happens in PHP rather than SQL because answers live in a JSON
     * map keyed by question key — the shape that keeps a response readable as
     * one row, and cheap enough at bootcamp volumes (hundreds, not millions).
     *
     * @return array<int, array{question: \App\Models\SurveyQuestion, answered: int, buckets: array<int, array{label: string, count: int, percent: float}>, top: ?array{label: string, count: int, percent: float}, average: ?float, texts: array<int, string>}>
     */
    public static function summarise(Survey $survey, Collection $responses): array
    {
        $out = [];

        foreach ($survey->liveQuestions() as $question) {
            $values = $responses
                ->map(fn (SurveyResponse $r) => $r->answer($question->key))
                ->filter(fn ($v) => $v !== null)
                ->values();

            $buckets = [];
            foreach ($question->buckets() as $bucket) {
                // Loose-ish match on purpose: scale answers come back as ints
                // from the JSON cast while buckets are strings.
                $count = $values->filter(fn ($v) => (string) $v === (string) $bucket)->count();

                $buckets[] = [
                    'label' => $question->labelFor($bucket),
                    'count' => $count,
                    'percent' => $values->count() ? round($count / $values->count() * 100) : 0,
                ];
            }

            $numeric = $values->filter(fn ($v) => is_numeric($v));

            // The one figure worth showing before anyone opens the question:
            // the option that won. Null when nothing has been answered yet.
            $top = collect($buckets)->sortByDesc('count')->first();

            $out[] = [
                'question' => $question,
                'answered' => $values->count(),
                'buckets' => $buckets,
                'top' => ($top && $top['count'] > 0) ? $top : null,
                'average' => $question->type === 'scale' && $numeric->count()
                    ? round($numeric->avg(), 1)
                    : null,
                'texts' => $question->type === 'text'
                    ? $values->map(fn ($v) => (string) $v)->all()
                    : [],
            ];
        }

        return $out;
    }
}
