<?php

namespace App\Models;

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
}
