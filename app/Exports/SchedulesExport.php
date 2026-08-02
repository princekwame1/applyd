<?php

namespace App\Exports;

use App\Models\Schedule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SchedulesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Schedule::ordered()->get();
    }

    public function headings(): array
    {
        return ['ID', 'Order', 'Week', 'Focus', 'Updated At'];
    }

    public function map($schedule): array
    {
        return [
            $schedule->id,
            $schedule->sort_order,
            $schedule->week_label,
            $schedule->focus,
            $schedule->updated_at->format('Y-m-d H:i'),
        ];
    }
}
