<?php

namespace App\Exports;

use App\Models\CourseEnrollment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CourseEnrollmentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return CourseEnrollment::with('course')->latest()->get();
    }

    public function headings(): array
    {
        return ['ID', 'Date', 'Name', 'Email', 'Phone', 'Course', 'Amount (GHS)', 'Status', 'Serial No', 'PIN', 'Application', 'Reference', 'Paid At'];
    }

    public function map($e): array
    {
        return [
            $e->id,
            $e->created_at->format('Y-m-d H:i'),
            $e->name,
            $e->email,
            $e->phone,
            $e->course?->title,
            number_format((float) $e->amount, 2),
            ucfirst($e->status),
            $e->serial_no,
            $e->pin,
            $e->completed_at ? 'Completed' : 'Incomplete',
            $e->reference,
            $e->paid_at?->format('Y-m-d H:i'),
        ];
    }
}
