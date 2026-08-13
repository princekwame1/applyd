<?php

namespace App\Exports;

use App\Models\PlanPurchase;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PlanPurchasesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return PlanPurchase::with('company')->latest()->get();
    }

    public function headings(): array
    {
        return ['ID', 'Company', 'Plan', 'Credits', 'Amount', 'Currency', 'Reference', 'Status', 'Paid At', 'Created'];
    }

    public function map($purchase): array
    {
        return [
            $purchase->id,
            $purchase->company?->name,
            $purchase->plan_name,
            $purchase->credits,
            (float) $purchase->amount,
            config('services.paystack.currency', 'GHS'),
            $purchase->reference,
            $purchase->status,
            $purchase->paid_at?->format('Y-m-d H:i'),
            $purchase->created_at->format('Y-m-d H:i'),
        ];
    }
}
