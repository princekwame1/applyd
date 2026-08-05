<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    public const LEVELS = ['Beginner', 'Intermediate', 'Advanced', 'All levels'];

    public const DEFAULT_FORM_FEE = 50;

    protected $fillable = [
        'title',
        'level',
        'duration',
        'price',
        'form_price',
        'description',
        'image',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'form_price' => 'decimal:2',
    ];

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function getFormFeeAttribute(): float
    {
        return (float) ($this->form_price ?? self::DEFAULT_FORM_FEE);
    }

    public function getFormFeeLabelAttribute(): string
    {
        $fee = $this->form_fee;

        return 'GHS '.number_format($fee, ($fee == (int) $fee) ? 0 : 2);
    }

    public function getTuitionFullAttribute(): float
    {
        return (float) ($this->price ?? 0);
    }

    public function getTuitionHalfAttribute(): float
    {
        return round($this->tuition_full / 2, 2);
    }

    public function requiresTuition(): bool
    {
        return $this->tuition_full > 0;
    }

    public static function money(float $amount): string
    {
        return 'GHS '.number_format($amount, ($amount == (int) $amount) ? 0 : 2);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getPriceLabelAttribute(): string
    {
        if ($this->price === null) {
            return 'Free';
        }

        return 'GHS '.number_format((float) $this->price, ($this->price == (int) $this->price) ? 0 : 2);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }
}
