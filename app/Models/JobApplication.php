<?php

namespace App\Models;

use App\Support\FitScore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobApplication extends Model
{
    public const STATUSES = ['pending', 'reviewed', 'shortlisted', 'rejected'];

    protected $fillable = [
        'job_opening_id',
        'full_name',
        'email',
        'phone',
        'cover_letter',
        'screening_answers',
        'cv_path',
        'cv_name',
        'cv_text',
        'status',
        // fit_score, fit_breakdown and meets_requirements stay off this list:
        // they are worked out by App\Support\FitScore and force-filled, so
        // nothing a candidate posts can claim its own score.
    ];

    protected function casts(): array
    {
        return [
            'screening_answers' => 'array',
            'fit_breakdown' => 'array',
            'fit_score' => 'integer',
            'meets_requirements' => 'boolean',
        ];
    }

    public function opening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class, 'job_opening_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    /** Scored at all — a job with no criteria scores nobody. */
    public function isScored(): bool
    {
        return $this->fit_score !== null;
    }

    public function isTopMatch(): bool
    {
        return $this->isScored() && $this->fit_score >= FitScore::TOP;
    }

    /** Missing a stated must-have. Never a rejection, only a flag. */
    public function missesRequirement(): bool
    {
        return $this->meets_requirements === false;
    }

    /** The criteria that were not met, for the one-line summary on the card. */
    public function missedLabels(): array
    {
        return collect($this->fit_breakdown ?: [])
            ->where('result', FitScore::MISSED)
            ->pluck('label')
            ->all();
    }
}
