<?php

namespace App\Models;

use App\Models\Concerns\Reviewable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOpening extends Model
{
    use Reviewable;

    public const TYPES = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote'];

    public const SECTORS = [
        'Agriculture',
        'Information Technology',
        'Finance & Banking',
        'Healthcare',
        'Education',
        'Manufacturing',
        'Construction',
        'Retail & Sales',
        'Marketing & Media',
        'Hospitality & Tourism',
        'Transport & Logistics',
        'Energy & Mining',
        'Telecommunications',
        'Legal',
        'Human Resources',
        'Real Estate',
        'Engineering',
        'Non-Profit / NGO',
        'Government',
        'Other',
    ];

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'location',
        'type',
        'sector',
        'salary_range',
        'deadline',
        'is_open',
        // Settable at creation only: every create() here is handed a
        // validated array, and 'status' is never a validated key, so nothing
        // a recruiter posts can reach it. reviewed_at/reviewed_by/review_note
        // stay off this list — approve() and reject() forceFill those.
        'status',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'is_open' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /**
     * Everything the public board is allowed to show.
     *
     * Four conditions, and the last two are the verification gate: the post
     * itself has to have been read, *and* the company behind it has to have
     * been identified. Either alone leaves the obvious hole — an unverified
     * poster whose first job slipped through, or a verified company posting
     * whatever it likes afterwards. This is the single place that decision is
     * made, so /jobs, the sector filter and the talent-pool counter cannot
     * disagree about what is live.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('job_openings.is_open', true)
            ->where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', now()->toDateString()))
            ->where('job_openings.status', self::APPROVED)
            ->whereHas('company', fn (Builder $q) => $q->where('companies.status', self::APPROVED));
    }

    /**
     * Whether this posting is on the board right now. Mirrors scopeOpen() for
     * a single row — the deadline and switch the recruiter controls, plus the
     * two approvals they don't.
     */
    public function getIsLiveAttribute(): bool
    {
        return $this->is_accepting
            && $this->isApproved()
            && (bool) $this->company?->isApproved();
    }

    /**
     * The recruiter's own half of it: open for applications as far as they are
     * concerned. Deliberately not the same question as is_live — the company
     * portal shows this next to the review status, so a recruiter can tell
     * "you closed it" from "we haven't approved it yet".
     */
    public function getIsAcceptingAttribute(): bool
    {
        return $this->is_open && (! $this->deadline || $this->deadline->endOfDay()->isFuture());
    }
}
