<?php

namespace App\Exports;

use App\Models\Course;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CoursesExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Course::ordered()->get();
    }

    public function headings(): array
    {
        return ['ID', 'Order', 'Title', 'Level', 'Duration', 'Description', 'Updated At'];
    }

    public function map($course): array
    {
        return [
            $course->id,
            $course->sort_order,
            $course->title,
            $course->level,
            $course->duration,
            $course->description,
            $course->updated_at->format('Y-m-d H:i'),
        ];
    }
}
