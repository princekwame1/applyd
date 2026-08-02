<?php

namespace App\Exports;

use App\Models\Registration;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RegistrationsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Registration::latest()->get();
    }

    public function headings(): array
    {
        return ['ID', 'Full Name', 'Gender', 'Age Range', 'Country', 'City', 'Phone', 'Email', 'Education', 'Tools', 'Marketing Opt-in', 'Registered At'];
    }

    public function map($registration): array
    {
        return [
            $registration->id,
            $registration->full_name,
            $registration->gender,
            $registration->age_range,
            $registration->country,
            $registration->city,
            $registration->full_phone,
            $registration->email,
            $registration->education,
            implode('; ', $registration->tools ?? []),
            $registration->marketing_opt_in ? 'Yes' : 'No',
            $registration->created_at->format('Y-m-d H:i'),
        ];
    }
}
