<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * One check-in form. Admins create as many as they need — one per session,
 * per cohort, per topic — and each keeps its own questions and its own answers.
 */
class Survey extends Model
{
    /** Slugs the public routes already use for something else. */
    public const RESERVED_SLUGS = ['thanks', 'index'];

    protected $fillable = [
        'slug',
        'name',
        'eyebrow',
        'blurb',
        'thanks',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** The public URL is /check-in/{slug}, so bind on the slug everywhere. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (Survey $survey) {
            if (empty($survey->slug) && $survey->name) {
                $survey->slug = static::uniqueSlug($survey->name, $survey->id);
            }
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'survey';

        if (in_array($base, static::RESERVED_SLUGS, true)) {
            $base .= '-survey';
        }

        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** The questions the public form shows, in display order. */
    public function liveQuestions(): Collection
    {
        return $this->questions()->active()->ordered()->get();
    }

    /** Open to the public: switched on, and with something to ask. */
    public function isOpen(): bool
    {
        return $this->is_active && $this->questions()->active()->exists();
    }

    public function getThanksMessageAttribute(): string
    {
        return $this->thanks ?: 'Thanks for your feedback.';
    }
}
