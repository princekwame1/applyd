<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPurchase extends Model
{
    protected $fillable = [
        'company_id',
        'recruiter_plan_id',
        'plan_name',
        'credits',
        'amount',
        'reference',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'credits' => 'integer',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(RecruiterPlan::class, 'recruiter_plan_id');
    }

    /** Only a settled payment adds credits — pending and failed ones never do. */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function getAmountLabelAttribute(): string
    {
        return config('services.paystack.currency', 'GHS').' '.number_format((float) $this->amount, 2);
    }
}
