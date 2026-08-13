<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_id',
        'answers',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /** Accepts a Survey, its id, or its slug — whichever the caller has. */
    public function scopeForSurvey(Builder $query, Survey|int|string $survey): Builder
    {
        if ($survey instanceof Survey) {
            return $query->where('survey_id', $survey->id);
        }

        if (is_int($survey)) {
            return $query->where('survey_id', $survey);
        }

        return $query->whereHas('survey', fn ($q) => $q->where('slug', $survey));
    }

    /** A single answer by question key, or null when the question was skipped. */
    public function answer(string $key): string|int|null
    {
        $value = ($this->answers ?? [])[$key] ?? null;

        return $value === '' ? null : $value;
    }

    public function getSurveyLabelAttribute(): string
    {
        return $this->survey?->name ?? '—';
    }
}
