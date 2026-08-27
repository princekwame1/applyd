<?php

namespace App\Models;

use App\Support\ContactMask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A CV dropped into the pool without applying to anything — the candidate
 * picks the sectors they want work in and waits for a matching job to be
 * posted. Contact details and the CV itself stay hidden until a company
 * spends an unlock credit.
 */
class TalentProfile extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'headline',
        'location',
        'sectors',
        'summary',
        'cv_path',
        'cv_name',
        'is_available',
    ];

    protected $casts = [
        'sectors' => 'array',
        'is_available' => 'boolean',
    ];

    public function unlocks(): HasMany
    {
        return $this->hasMany(CvUnlock::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    /**
     * Candidates who want work in any of these sectors.
     *
     * `sectors` is a JSON array, so this OR-s whereJsonContains() per sector —
     * exact element matching on MySQL (json_contains) and SQLite (json_each),
     * never a substring. Same shape as the registrations tool filter.
     *
     * @param  array<int, string>  $sectors
     */
    public function scopeInSectors(Builder $query, array $sectors): Builder
    {
        $sectors = array_values(array_filter($sectors));

        if (! $sectors) {
            // No sectors to match on means no matches — not "everyone".
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($sectors) {
            foreach ($sectors as $sector) {
                $q->orWhereJsonContains('sectors', $sector);
            }
        });
    }

    public function sectorList(): array
    {
        return array_values(array_filter((array) ($this->sectors ?? [])));
    }

    /** What a company sees before paying: enough to judge, not enough to contact. */
    public function getMaskedNameAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->full_name)) ?: [];
        $first = $parts[0] ?? 'Candidate';
        $initial = isset($parts[1]) ? ' '.Str::upper(Str::substr($parts[1], 0, 1)).'.' : '';

        return $first.$initial;
    }

    /**
     * The headline and summary as a company that has NOT paid may see them.
     *
     * These two are the only candidate-authored text on a locked card, which
     * makes them the one way contact details can cross the paywall — a phone
     * number in the headline is worth exactly as much as the phone column, and
     * costs nothing. `ContactMask` takes it back out. The raw attribute is what
     * an unlocked card and the dashboard render; nothing is lost, only hidden.
     */
    public function getPublicHeadlineAttribute(): string
    {
        return ContactMask::scrub($this->headline);
    }

    public function getPublicSummaryAttribute(): string
    {
        return ContactMask::scrub($this->summary);
    }
}
