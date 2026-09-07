<?php

namespace App\Exports;

use App\Models\JobOpening;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class JobPostingsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return JobOpening::with(['company', 'reviewer'])
            ->withCount('applications')
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Title', 'Company', 'Company Verified', 'Sector', 'Type', 'Location',
            'Deadline', 'Applicants', 'Review Status', 'On The Board', 'Reviewed By',
            'Reviewed At', 'Reason If Rejected', 'Posted At',
        ];
    }

    public function map($opening): array
    {
        return [
            $opening->id,
            $opening->title,
            $opening->company?->name,
            $opening->company?->isApproved() ? 'Yes' : 'No',
            $opening->sector,
            $opening->type,
            $opening->location,
            $opening->deadline?->format('Y-m-d'),
            $opening->applications_count,
            $opening->status_label,
            $opening->is_live ? 'Live' : 'Not live',
            $opening->reviewer?->name,
            $opening->reviewed_at?->format('Y-m-d H:i'),
            $opening->review_note,
            $opening->created_at->format('Y-m-d H:i'),
        ];
    }
}
