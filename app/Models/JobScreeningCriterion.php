<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One thing a role asks for, and what it is worth.
 *
 * A `keyword` is matched against the CV text and the cover letter and is the
 * recruiter's own rubric — it is **never rendered on the public apply form**,
 * because a list of the words that score is an invitation to paste them in.
 * A `question` is asked at apply time and answered by the candidate.
 */
class JobScreeningCriterion extends Model
{
    public const KEYWORD = 'keyword';

    public const QUESTION = 'question';

    public const KINDS = [self::KEYWORD, self::QUESTION];

    /** How a question is answered. Each one scores against `expected`. */
    public const YES_NO = 'yes_no';

    public const NUMBER = 'number';

    public const CHOICE = 'choice';

    public const ANSWER_TYPES = [self::YES_NO, self::NUMBER, self::CHOICE];

    protected $fillable = [
        'job_opening_id',
        'kind',
        'key',
        'label',
        'answer_type',
        'options',
        'expected',
        'weight',
        'is_knockout',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'expected' => 'array',
            'weight' => 'integer',
            'is_knockout' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function opening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class, 'job_opening_id');
    }

    public function isKeyword(): bool
    {
        return $this->kind === self::KEYWORD;
    }

    public function isQuestion(): bool
    {
        return $this->kind === self::QUESTION;
    }

    /**
     * The handle answers are filed under. Derived from the label the first
     * time only: it is the key in `job_applications.screening_answers`, so a
     * later rename must not move it and orphan what has been collected.
     */
    public static function uniqueKey(int $openingId, string $label): string
    {
        $base = Str::limit(Str::slug($label, '_'), 50, '') ?: 'question';
        $key = $base;
        $i = 2;

        while (static::where('job_opening_id', $openingId)->where('key', $key)->exists()) {
            $key = $base.'_'.$i++;
        }

        return $key;
    }

    /** The validation a public apply form owes this question. */
    public function rules(): array
    {
        return match ($this->answer_type) {
            self::YES_NO => ['required', 'in:yes,no'],
            self::NUMBER => ['required', 'numeric', 'min:0', 'max:99'],
            self::CHOICE => ['required', 'string', 'in:'.implode(',', $this->options ?: [])],
            default => ['nullable'],
        };
    }

    /** What the recruiter is asking for, in words, for the criteria screen. */
    public function getExpectationLabelAttribute(): string
    {
        if ($this->isKeyword()) {
            return 'Mentioned in the CV or cover letter';
        }

        $expected = $this->expected ?: [];

        return match ($this->answer_type) {
            self::YES_NO => 'Answer: Yes',
            self::NUMBER => 'At least '.(float) ($expected[0] ?? 0),
            self::CHOICE => 'One of: '.implode(', ', $expected),
            default => '—',
        };
    }
}
