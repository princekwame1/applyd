<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuestionnaireResponse extends Model
{
    protected $fillable = [
        'questionnaire_id',
        'reference',
        'answers',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (QuestionnaireResponse $response) {
            $response->reference ??= static::uniqueReference();
        });
    }

    /** A short code the submitter can quote back to us. */
    public static function uniqueReference(): string
    {
        do {
            $reference = 'R-'.strtoupper(Str::random(8));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(QuestionnaireFile::class);
    }

    public function scopeForQuestionnaire(Builder $query, Questionnaire|int $questionnaire): Builder
    {
        return $query->where('questionnaire_id', $questionnaire instanceof Questionnaire ? $questionnaire->id : $questionnaire);
    }

    /** The answer to one question, or null when it was skipped. */
    public function answer(string $key): mixed
    {
        return ($this->answers ?? [])[$key] ?? null;
    }

    public function fileFor(string $key): ?QuestionnaireFile
    {
        return $this->files->firstWhere('question_key', $key);
    }
}
