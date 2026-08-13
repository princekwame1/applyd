<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RecruiterPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'cv_credits',
        'blurb',
        'features',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cv_credits' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (RecruiterPlan $plan) {
            if (empty($plan->slug) && $plan->name) {
                $plan->slug = static::uniqueSlug($plan->name, $plan->id);
            }
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'plan';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(PlanPurchase::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function featureList(): array
    {
        return array_values(array_filter((array) ($this->features ?? []), fn ($f) => trim((string) $f) !== ''));
    }

    public function getPriceLabelAttribute(): string
    {
        return config('services.paystack.currency', 'GHS').' '.number_format((float) $this->price, 2);
    }
}
