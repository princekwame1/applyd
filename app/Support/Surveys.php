<?php

namespace App\Support;

use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Support\Collection;

/**
 * The two check-in surveys ("Pulse Check") and the aggregation behind the
 * dashboard results screen.
 *
 * The survey *types* are structural and live in config/surveys.php; the
 * *questions* are DB-driven (`survey_questions`) and admin-editable.
 */
class Surveys
{
    /** @return array<string, array{label: string, eyebrow: string, blurb: string, thanks: string}> */
    public static function types(): array
    {
        return config('surveys.types', []);
    }

    public static function exists(string $surveyType): bool
    {
        return array_key_exists($surveyType, static::types());
    }

    public static function label(string $surveyType): string
    {
        return static::types()[$surveyType]['label'] ?? ucfirst($surveyType);
    }

    public static function copy(string $surveyType): array
    {
        return static::types()[$surveyType] ?? [
            'label' => ucfirst($surveyType),
            'eyebrow' => '',
            'blurb' => '',
            'thanks' => 'Thanks for your feedback.',
        ];
    }

    /** The live questions for one survey, in display order. */
    public static function questions(string $surveyType): Collection
    {
        return SurveyQuestion::forSurvey($surveyType)->active()->ordered()->get();
    }

    /**
     * Roll responses up per question for the results screen.
     *
     * Counting happens in PHP rather than SQL because answers live in a JSON
     * map keyed by question key — the shape that keeps a response readable as
     * one row, and cheap enough at bootcamp volumes (hundreds, not millions).
     *
     * @return array<int, array{question: SurveyQuestion, answered: int, buckets: array<int, array{label: string, count: int, percent: float}>, texts: array<int, string>}>
     */
    public static function summarise(string $surveyType, Collection $responses): array
    {
        $out = [];

        foreach (static::questions($surveyType) as $question) {
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

            $out[] = [
                'question' => $question,
                'answered' => $values->count(),
                'buckets' => $buckets,
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
