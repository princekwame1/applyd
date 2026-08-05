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
        'attendance',
        'form_price',
        'description',
        'image',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'attendance' => 'array',
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

    /**
     * The attendance modes offered for this course, with their prices.
     * A mode is offered if it has a price set. Falls back to a single
     * "Standard" option using the base price for legacy courses.
     *
     * @return array<int, array{key:string,label:string,price:float}>
     */
    public function attendanceOptions(): array
    {
        $options = [];

        foreach ((array) $this->attendance as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $options[] = [
                'key' => \Illuminate\Support\Str::slug($label) ?: 'option-'.count($options),
                'label' => $label,
                'price' => (float) ($row['price'] ?? 0),
            ];
        }

        if (empty($options) && (float) $this->price > 0) {
            $options[] = ['key' => 'standard', 'label' => 'Standard', 'price' => (float) $this->price];
        }

        return $options;
    }

    public function priceForAttendance(?string $key): float
    {
        foreach ($this->attendanceOptions() as $option) {
            if ($option['key'] === $key) {
                return $option['price'];
            }
        }

        return (float) ($this->price ?? 0);
    }

    /** Lowest available attendance price — used for "from GHS x" displays. */
    public function getTuitionFullAttribute(): float
    {
        $prices = array_column($this->attendanceOptions(), 'price');

        return $prices ? (float) min($prices) : (float) ($this->price ?? 0);
    }

    public function requiresTuition(): bool
    {
        return $this->tuition_full > 0;
    }

    public function hasMultipleAttendance(): bool
    {
        return count($this->attendanceOptions()) > 1;
    }

    public function attendanceLabel(?string $key): string
    {
        if (! $key) {
            return '—';
        }

        foreach ($this->attendanceOptions() as $option) {
            if ($option['key'] === $key) {
                return $option['label'];
            }
        }

        return (string) \Illuminate\Support\Str::of($key)->replace('-', ' ')->title();
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
