<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * One admin-built form. Questions hang off it, answers hang off those, and the
 * only thing that has to be handed out is the public link: /forms/{slug}.
 */
class Questionnaire extends Model
{
    /**
     * Slugs some other route already claims — `thanks` on the public side,
     * the rest on the dashboard, where a form is also addressed by slug.
     */
    public const RESERVED_SLUGS = ['thanks', 'index', 'closed', 'responses', 'questions', 'files', 'create', 'edit'];

    protected $fillable = [
        'slug',
        'title',
        'description',
        'success_message',
        'submit_label',
        'is_published',
        'opens_at',
        'closes_at',
        'response_limit',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
    ];

    /** The public URL is /forms/{slug}, so bind on the slug everywhere. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (Questionnaire $questionnaire) {
            if (empty($questionnaire->slug) && $questionnaire->title) {
                $questionnaire->slug = static::uniqueSlug($questionnaire->title, $questionnaire->id);
            }
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'form';

        if (in_array($base, static::RESERVED_SLUGS, true)) {
            $base .= '-form';
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
        return $this->hasMany(QuestionnaireQuestion::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuestionnaireResponse::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /** The questions the public form shows, in display order. */
    public function liveQuestions(): Collection
    {
        return $this->questions()->active()->ordered()->get();
    }

    /**
     * Why the form isn't taking answers right now, or null when it is.
     * One method so the public page, the dashboard badge and the POST guard
     * can never disagree about whether a form is open.
     */
    public function closedReason(): ?string
    {
        if (! $this->is_published) {
            return 'This form isn\'t accepting responses.';
        }

        if ($this->opens_at && $this->opens_at->isFuture()) {
            return 'This form opens on '.$this->opens_at->format('M j, Y \a\t g:ia').'.';
        }

        if ($this->closes_at && $this->closes_at->isPast()) {
            return 'This form closed on '.$this->closes_at->format('M j, Y').'.';
        }

        if ($this->response_limit && $this->responses()->count() >= $this->response_limit) {
            return 'This form has reached its limit of '.number_format($this->response_limit).' responses.';
        }

        return null;
    }

    public function isOpen(): bool
    {
        return $this->closedReason() === null;
    }

    public function publicUrl(): string
    {
        return route('forms.show', $this);
    }

    public function getThanksMessageAttribute(): string
    {
        return $this->success_message ?: 'Thanks — your response has been recorded.';
    }

    public function getSubmitTextAttribute(): string
    {
        return $this->submit_label ?: 'Submit';
    }
}
