<?php

namespace App\Exports;

use App\Models\RecruiterPlan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RecruiterPlansExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return RecruiterPlan::withCount(['purchases as paid_purchases_count' => fn ($q) => $q->where('status', 'paid')])
            ->ordered()
            ->get();
    }

    public function headings(): array
    {
        return ['ID', 'Order', 'Plan', 'Slug', 'Price', 'CV Unlocks', 'Sold', 'Status', 'Features', 'Updated At'];
    }

    public function map($plan): array
    {
        return [
            $plan->id,
            $plan->sort_order,
            $plan->name,
            $plan->slug,
            (float) $plan->price,
            $plan->cv_credits,
            $plan->paid_purchases_count,
            $plan->is_active ? 'On sale' : 'Hidden',
            implode(' | ', $plan->featureList()),
            $plan->updated_at->format('Y-m-d H:i'),
        ];
    }
}
