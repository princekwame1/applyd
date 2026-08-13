<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Company extends Model
{
    /** Outcomes of an unlock attempt, mapped to flash messages by the controller. */
    public const UNLOCK_OK = 'ok';

    public const UNLOCK_ALREADY = 'already';

    public const UNLOCK_NO_CREDITS = 'no_credits';

    public const UNLOCK_NOT_MATCHED = 'not_matched';

    protected $fillable = [
        'user_id',
        'name',
        'website',
        'location',
        'logo',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function openings(): HasMany
    {
        return $this->hasMany(JobOpening::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(PlanPurchase::class);
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(CvUnlock::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/'.$this->logo) : null;
    }

    // ---------------------------------------------------------------- credits

    /** Credits from every settled purchase. Top-ups simply add another row. */
    public function creditsBought(): int
    {
        return (int) $this->purchases()->paid()->sum('credits');
    }

    public function creditsUsed(): int
    {
        return $this->unlocks()->count();
    }

    public function creditsLeft(): int
    {
        return max(0, $this->creditsBought() - $this->creditsUsed());
    }

    // ---------------------------------------------------------- talent access

    /**
     * The sectors this company hires in — taken from every job it has posted,
     * open or closed. Closing a job must not make candidates it already paid
     * to unlock vanish from the list.
     *
     * @return array<int, string>
     */
    public function jobSectors(): array
    {
        return $this->openings()
            ->whereNotNull('sector')
            ->where('sector', '!=', '')
            ->distinct()
            ->pluck('sector')
            ->all();
    }

    /**
     * Candidates this company is allowed to see: anyone whose wanted sectors
     * overlap a job it has published, plus anyone it has already unlocked.
     */
    public function matchingTalent(): Builder
    {
        $sectors = $this->jobSectors();
        $unlockedIds = $this->unlocks()->pluck('talent_profile_id')->all();

        return TalentProfile::query()
            ->with(['unlocks' => fn ($q) => $q->where('company_id', $this->id)])
            ->where(function (Builder $q) use ($sectors, $unlockedIds) {
                $q->where(fn (Builder $inner) => $inner->available()->inSectors($sectors));

                if ($unlockedIds) {
                    $q->orWhereIn('id', $unlockedIds);
                }
            });
    }

    public function hasUnlocked(TalentProfile $profile): bool
    {
        return $this->unlocks()->where('talent_profile_id', $profile->id)->exists();
    }

    /**
     * Spend one credit on a candidate. Idempotent by the unique index on
     * (company_id, talent_profile_id): a second click costs nothing.
     */
    public function unlockCv(TalentProfile $profile): string
    {
        if ($this->hasUnlocked($profile)) {
            return static::UNLOCK_ALREADY;
        }

        if (! in_array($profile->id, $this->matchingTalent()->pluck('id')->all(), true)) {
            return static::UNLOCK_NOT_MATCHED;
        }

        return DB::transaction(function () use ($profile) {
            // Re-checked inside the transaction so two tabs can't both spend
            // the last credit.
            if ($this->creditsLeft() < 1) {
                return static::UNLOCK_NO_CREDITS;
            }

            $this->unlocks()->create(['talent_profile_id' => $profile->id]);

            return static::UNLOCK_OK;
        });
    }
}
