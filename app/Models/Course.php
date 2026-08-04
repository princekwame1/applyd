<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    public const LEVELS = ['Beginner', 'Intermediate', 'Advanced', 'All levels'];

    protected $fillable = [
        'title',
        'level',
        'duration',
        'price',
        'description',
        'image',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

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
