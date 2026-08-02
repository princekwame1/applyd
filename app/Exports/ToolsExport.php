<?php

namespace App\Exports;

use App\Models\Tool;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ToolsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Tool::ordered()->get();
    }

    public function headings(): array
    {
        return ['ID', 'Order', 'Name', 'Category', 'Blurb', 'Updated At'];
    }

    public function map($tool): array
    {
        return [
            $tool->id,
            $tool->sort_order,
            $tool->name,
            $tool->category,
            $tool->blurb,
            $tool->updated_at->format('Y-m-d H:i'),
        ];
    }
}
