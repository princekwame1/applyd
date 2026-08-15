<?php

namespace App\Exports;

use App\Models\FinanceTransaction;
use App\Support\Finance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinanceTransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(protected ?string $from = null, protected ?string $to = null) {}

    public function collection(): Collection
    {
        return FinanceTransaction::query()
            ->with(['category', 'recorder'])
            ->withCount('documents')
            ->betweenDates($this->from, $this->to)
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Reference', 'Date', 'Type', 'Category',
            'Money in ('.Finance::currency().')',
            'Money out ('.Finance::currency().')',
            'From / To', 'Method', 'Document no.', 'Note', 'Documents', 'Recorded by',
        ];
    }

    public function map($transaction): array
    {
        // Money in and money out get their own columns rather than one signed
        // one: it's what a spreadsheet wants to total, and it reads the way a
        // cash book does.
        return [
            $transaction->reference,
            $transaction->occurred_on->format('Y-m-d'),
            $transaction->typeLabel(),
            $transaction->category?->name ?? 'Uncategorised',
            $transaction->isIncome() ? (float) $transaction->amount : null,
            $transaction->isIncome() ? null : (float) $transaction->amount,
            $transaction->party,
            $transaction->method,
            $transaction->document_no,
            $transaction->note,
            $transaction->documents_count,
            $transaction->recorder?->name,
        ];
    }
}
