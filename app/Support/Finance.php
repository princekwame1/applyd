<?php

namespace App\Support;

use App\Models\FinanceTransaction;
use Illuminate\Database\Eloquent\Builder;

class Finance
{
    /**
     * The currency the books are kept in. Follows the Paystack setting so the
     * ledger and what people actually paid can't disagree.
     */
    public static function currency(): string
    {
        return config('services.paystack.currency', 'GHS');
    }

    /** e.g. "GHS 1,240.50", or "−GHS 1,240.50" when asked to show the sign. */
    public static function money(mixed $amount, bool $signed = false): string
    {
        $value = (float) $amount;
        $sign = $signed && $value < 0 ? '−' : '';

        return $sign.static::currency().' '.number_format(abs($value), 2);
    }

    /**
     * Money in, money out and the difference over a query. Summed in SQL — a
     * ledger grows without limit, so this must never load rows to add them up.
     *
     * @return array{income: float, expense: float, net: float, count: int}
     */
    public static function summarise(Builder $query): array
    {
        $row = $query
            ->clone()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as income_total,
                 COALESCE(SUM(CASE WHEN type = ? THEN amount ELSE 0 END), 0) as expense_total,
                 COUNT(*) as entries',
                [FinanceTransaction::INCOME, FinanceTransaction::EXPENSE],
            )
            ->first();

        $income = (float) ($row->income_total ?? 0);
        $expense = (float) ($row->expense_total ?? 0);

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
            'count' => (int) ($row->entries ?? 0),
        ];
    }

    /**
     * Totals per category for one side of the books, biggest first — the
     * "where is it all going?" breakdown.
     *
     * @return array<int, array{name: string, total: float, share: float}>
     */
    public static function byCategory(Builder $query, string $type): array
    {
        $rows = $query
            ->clone()
            ->where('finance_transactions.type', $type)
            ->leftJoin('finance_categories', 'finance_categories.id', '=', 'finance_transactions.finance_category_id')
            ->selectRaw('finance_categories.name as name, SUM(finance_transactions.amount) as total')
            ->groupBy('finance_categories.name')
            ->orderByDesc('total')
            ->get();

        $grand = (float) $rows->sum('total');

        return $rows->map(fn ($row) => [
            'name' => $row->name ?: 'Uncategorised',
            'total' => (float) $row->total,
            'share' => $grand > 0 ? round((float) $row->total / $grand * 100) : 0,
        ])->all();
    }
}
