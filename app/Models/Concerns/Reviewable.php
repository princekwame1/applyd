<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A row that an admin approves or rejects.
 *
 * Shared by Company (is this job poster who they say they are?) and
 * JobOpening (is this posting real?), because the two queues want identical
 * mechanics and any drift between them would show up as a job that is live
 * while its own status says otherwise. The statuses, the transitions and the
 * stamping of who decided all live here once.
 */
trait Reviewable
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public const STATUSES = [self::PENDING, self::APPROVED, self::REJECTED];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where($this->getTable().'.status', self::PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where($this->getTable().'.status', self::APPROVED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where($this->getTable().'.status', self::REJECTED);
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::REJECTED;
    }

    /**
     * Approve, and forget any previous rejection reason — leaving it behind
     * would show a live record still explaining why it was turned down.
     *
     * Returns false when it was already approved, so a caller can tell a
     * fresh decision from a repeated click and not send a second email.
     */
    public function approve(?User $by = null): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        $this->forceFill([
            'status' => self::APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => $by?->id,
            'review_note' => null,
        ])->save();

        return true;
    }

    /**
     * Reject with a reason. The reason is the whole point — "declined" with
     * no explanation is a support call, and the recruiter cannot fix what
     * they have not been told.
     */
    public function reject(string $reason, ?User $by = null): bool
    {
        if ($this->isRejected() && $this->review_note === $reason) {
            return false;
        }

        $this->forceFill([
            'status' => self::REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => $by?->id,
            'review_note' => $reason,
        ])->save();

        return true;
    }

    /** Back into the queue — used when an approved record is edited. */
    public function sendBackForReview(): void
    {
        $this->forceFill([
            'status' => self::PENDING,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'review_note' => null,
        ])->save();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            default => 'Pending review',
        };
    }
}
