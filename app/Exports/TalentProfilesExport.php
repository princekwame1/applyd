<?php

namespace App\Exports;

use App\Models\TalentProfile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TalentProfilesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return TalentProfile::withCount('unlocks')->latest()->get();
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Email', 'Phone', 'Headline', 'Location', 'Sectors', 'Status', 'Unlocks', 'CV File', 'Dropped'];
    }

    public function map($profile): array
    {
        return [
            $profile->id,
            $profile->full_name,
            $profile->email,
            $profile->phone,
            $profile->headline,
            $profile->location,
            implode(', ', $profile->sectorList()),
            $profile->is_available ? 'Open to work' : 'Paused',
            $profile->unlocks_count,
            $profile->cv_name,
            $profile->created_at->format('Y-m-d H:i'),
        ];
    }
}
