<?php

namespace App\Support;

use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobScreeningCriterion as Criterion;
use Illuminate\Support\Collection;

/**
 * How well an application answers what the role asked for.
 *
 * The single place a score is worked out — the apply form, the re-score after
 * a recruiter edits their criteria and the applicants screen all call in
 * here, so a number on screen can never disagree with how it was reached.
 *
 * Three results per criterion, and the third is the important one. A keyword
 * we could not look for (the CV would not open and there is no cover letter)
 * and a question that was never asked (the criterion was added after this
 * person applied) are **unknown**, not missed: they are left out of the sum
 * entirely rather than counted against the candidate. A score built by
 * penalising people for questions nobody put to them is worse than no score.
 *
 * Nothing here rejects anybody. A missed must-have flags the application and
 * sorts it down; the decision stays with the recruiter, who can still read
 * every word of it.
 */
class FitScore
{
    public const MET = 'met';

    public const MISSED = 'missed';

    public const UNKNOWN = 'unknown';

    /** At or above this, an applicant is shown as a top match. */
    public const TOP = 70;

    /**
     * @return array{score: ?int, meets: ?bool, lines: array<int, array<string, mixed>>}
     */
    public static function evaluate(JobApplication $application, Collection $criteria): array
    {
        $answers = $application->screening_answers ?: [];
        $haystack = trim(($application->cv_text ?? '').' '.($application->cover_letter ?? ''));

        $lines = [];
        $met = 0;
        $possible = 0;
        $knockoutMissed = false;
        $hasKnockout = false;

        foreach ($criteria as $criterion) {
            $weight = max(1, (int) $criterion->weight);

            $result = $criterion->isKeyword()
                ? static::scoreKeyword($criterion, $haystack)
                : static::scoreQuestion($criterion, $answers);

            $lines[] = [
                'label' => $criterion->label,
                'kind' => $criterion->kind,
                'weight' => $weight,
                'knockout' => (bool) $criterion->is_knockout,
                'result' => $result,
                'detail' => static::detail($criterion, $answers, $result),
            ];

            if ($criterion->is_knockout) {
                $hasKnockout = true;

                if ($result === self::MISSED) {
                    $knockoutMissed = true;
                }
            }

            // Unknown never lands in the sum — neither side of it.
            if ($result === self::UNKNOWN) {
                continue;
            }

            $possible += $weight;

            if ($result === self::MET) {
                $met += $weight;
            }
        }

        return [
            // Null, not zero: nothing was checkable, and a zero would read as
            // "this person matched none of it".
            'score' => $possible > 0 ? (int) round($met / $possible * 100) : null,
            'meets' => $hasKnockout ? ! $knockoutMissed : null,
            'lines' => $lines,
        ];
    }

    /** Work out an application's score and store it. */
    public static function store(JobApplication $application, ?Collection $criteria = null): JobApplication
    {
        $criteria ??= $application->opening?->screeningCriteria()->get() ?? collect();

        $result = static::evaluate($application, $criteria);

        $application->forceFill([
            'fit_score' => $result['score'],
            'fit_breakdown' => $result['lines'],
            'meets_requirements' => $result['meets'],
        ])->save();

        return $application;
    }

    /**
     * Re-score everyone who has already applied. Called whenever the criteria
     * change, so the list is never a mix of old and new rubrics — a recruiter
     * comparing two scores has to be comparing the same thing.
     */
    public static function rescore(JobOpening $opening): int
    {
        $criteria = $opening->screeningCriteria()->get();
        $count = 0;

        $opening->applications()->chunkById(200, function ($applications) use ($criteria, &$count) {
            foreach ($applications as $application) {
                static::store($application, $criteria);
                $count++;
            }
        });

        return $count;
    }

    private static function scoreKeyword(Criterion $criterion, string $haystack): string
    {
        if ($haystack === '') {
            return self::UNKNOWN;
        }

        return static::mentions($haystack, $criterion->label) ? self::MET : self::MISSED;
    }

    /**
     * A whole-word, case- and accent-insensitive match, with the boundaries
     * only where they mean something: "C++" and ".NET" end in punctuation, so
     * demanding a word boundary there would never match, while a plain "Java"
     * without one would match inside "JavaScript".
     */
    public static function mentions(string $haystack, string $term): bool
    {
        $term = trim($term);

        if ($term === '') {
            return false;
        }

        $pattern = preg_quote($term, '/');
        $pattern = str_replace('\ ', '\s+', $pattern);

        $before = preg_match('/^[\p{L}\p{N}]/u', $term) ? '(?<![\p{L}\p{N}])' : '';
        $after = preg_match('/[\p{L}\p{N}]$/u', $term) ? '(?![\p{L}\p{N}])' : '';

        return (bool) preg_match('/'.$before.$pattern.$after.'/iu', $haystack);
    }

    private static function scoreQuestion(Criterion $criterion, array $answers): string
    {
        $key = (string) $criterion->key;

        // Asked of nobody who applied before it existed.
        if ($key === '' || ! array_key_exists($key, $answers) || $answers[$key] === null || $answers[$key] === '') {
            return self::UNKNOWN;
        }

        $answer = $answers[$key];
        $expected = $criterion->expected ?: [];

        $ok = match ($criterion->answer_type) {
            Criterion::YES_NO => strtolower((string) $answer) === 'yes',
            Criterion::NUMBER => is_numeric($answer) && (float) $answer >= (float) ($expected[0] ?? 0),
            Criterion::CHOICE => in_array((string) $answer, array_map('strval', $expected), true),
            default => false,
        };

        return $ok ? self::MET : self::MISSED;
    }

    /** What the recruiter is shown next to the tick or the cross. */
    private static function detail(Criterion $criterion, array $answers, string $result): string
    {
        if ($criterion->isKeyword()) {
            return match ($result) {
                self::MET => 'Found in the CV or cover letter',
                self::MISSED => 'Not mentioned',
                default => 'CV could not be read as text — check this one by hand',
            };
        }

        if ($result === self::UNKNOWN) {
            return 'Not asked — this criterion was added after they applied';
        }

        $answer = $answers[$criterion->key] ?? '';

        return 'Answered: '.(is_array($answer) ? implode(', ', $answer) : (string) $answer);
    }
}
