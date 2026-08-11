<?php

namespace App\Models;

use App\Support\Surveys;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    protected $fillable = [
        'survey_type',
        'answers',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    public function scopeForSurvey(Builder $query, string $surveyType): Builder
    {
        return $query->where('survey_type', $surveyType);
    }

    /** A single answer by question key, or null when the question was skipped. */
    public function answer(string $key): string|int|null
    {
        $value = ($this->answers ?? [])[$key] ?? null;

        return $value === '' ? null : $value;
    }

    public function getSurveyLabelAttribute(): string
    {
        return Surveys::label($this->survey_type);
    }
}
