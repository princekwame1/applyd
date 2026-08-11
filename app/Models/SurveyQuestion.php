<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class SurveyQuestion extends Model
{
    public const TYPES = ['choice', 'scale', 'text'];

    protected $fillable = [
        'survey_type',
        'key',
        'type',
        'prompt',
        'options',
        'placeholder',
        'required',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'required' => 'boolean',
        'active' => 'boolean',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeForSurvey(Builder $query, string $surveyType): Builder
    {
        return $query->where('survey_type', $surveyType);
    }

    /** Choice options / scale labels, always an array even when the column is null. */
    public function optionList(): array
    {
        return array_values(array_filter((array) ($this->options ?? []), fn ($o) => $o !== null && $o !== ''));
    }

    /**
     * The fixed set of values this question can be counted over, in display
     * order. Free text has no buckets — it's listed verbatim instead.
     */
    public function buckets(): array
    {
        return match ($this->type) {
            'choice' => $this->optionList(),
            'scale' => array_map('strval', range(1, max(1, count($this->optionList())))),
            default => [],
        };
    }

    /**
     * How a stored answer reads on the results screen. A scale answer is a
     * number on its own, so pair it with the label the participant saw.
     */
    public function labelFor(string|int $value): string
    {
        if ($this->type !== 'scale') {
            return (string) $value;
        }

        $label = $this->optionList()[((int) $value) - 1] ?? null;

        return $label ? $value.' · '.$label : (string) $value;
    }

    /** Validation rules for this question's slot in the submitted answers array. */
    public function rules(): array
    {
        $presence = $this->required ? 'required' : 'nullable';

        return match ($this->type) {
            'choice' => [$presence, 'string', Rule::in($this->optionList())],
            'scale' => [$presence, 'integer', 'min:1', 'max:'.max(1, count($this->optionList()))],
            default => [$presence, 'string', 'max:2000'],
        };
    }
}
