<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One line in the books. Income and expense are the same shape — `type` is the
 * only thing separating them — so every total, filter and export handles both
 * without a second code path.
 */
class FinanceTransaction extends Model
{
    public const INCOME = 'income';

    public const EXPENSE = 'expense';

    public const TYPES = [
        self::INCOME => 'Money in',
        self::EXPENSE => 'Money out',
    ];

    /** How the money moved. Free-form in the DB, picked from here in the UI. */
    public const METHODS = ['Cash', 'Bank transfer', 'Mobile money', 'Card', 'Cheque', 'Other'];

    protected $fillable = [
        'reference',
        'type',
        'finance_category_id',
        'amount',
        'occurred_on',
        'party',
        'method',
        'document_no',
        'note',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_on' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (FinanceTransaction $transaction) {
            $transaction->reference ??= static::uniqueReference($transaction->type);
        });
    }

    /** A short human handle for one entry, e.g. IN-7QK3M2 / EX-4B9WPD. */
    public static function uniqueReference(?string $type = null): string
    {
        $prefix = $type === self::EXPENSE ? 'EX' : 'IN';

        do {
            $reference = $prefix.'-'.strtoupper(Str::random(6));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'finance_category_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FinanceDocument::class);
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', self::INCOME);
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', self::EXPENSE);
    }

    /** Inclusive on both ends; either bound may be left open. */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('occurred_on', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('occurred_on', '<=', $to));
    }

    public function isIncome(): bool
    {
        return $this->type === self::INCOME;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** What this line does to the net position: income adds, expense subtracts. */
    public function signedAmount(): string
    {
        return $this->isIncome() ? (string) $this->amount : '-'.$this->amount;
    }

    public function invoice(): ?FinanceDocument
    {
        return $this->documents->firstWhere('kind', FinanceDocument::INVOICE);
    }

    public function receipt(): ?FinanceDocument
    {
        return $this->documents->firstWhere('kind', FinanceDocument::RECEIPT);
    }
}
