<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobOpening extends Model
{
    public const TYPES = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Remote'];

    public const SECTORS = [
        'Agriculture',
        'Information Technology',
        'Finance & Banking',
        'Healthcare',
        'Education',
        'Manufacturing',
        'Construction',
        'Retail & Sales',
        'Marketing & Media',
        'Hospitality & Tourism',
        'Transport & Logistics',
        'Energy & Mining',
        'Telecommunications',
        'Legal',
        'Human Resources',
        'Real Estate',
        'Engineering',
        'Non-Profit / NGO',
        'Government',
        'Other',
    ];

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'location',
        'type',
        'sector',
        'salary_range',
        'deadline',
        'is_open',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'is_open' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_open', true)
            ->where(fn ($q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', now()->toDateString()));
    }

    public function getIsAcceptingAttribute(): bool
    {
        return $this->is_open && (! $this->deadline || $this->deadline->endOfDay()->isFuture());
    }
}
