<?php

namespace App\Exports;

use App\Models\Company;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CompaniesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Company::with(['user', 'reviewer'])
            ->withCount('openings')
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Company', 'Ghana Card', 'Contact', 'Email', 'Location', 'Website',
            'Jobs Posted', 'Status', 'Reviewed By', 'Reviewed At', 'Reason If Rejected', 'Registered At',
        ];
    }

    public function map($company): array
    {
        return [
            $company->id,
            $company->name,
            $company->ghana_card ?: 'Not provided',
            $company->user?->name,
            $company->user?->email,
            $company->location,
            $company->website,
            $company->openings_count,
            $company->status_label,
            $company->reviewer?->name,
            $company->reviewed_at?->format('Y-m-d H:i'),
            $company->review_note,
            $company->created_at->format('Y-m-d H:i'),
        ];
    }
}
