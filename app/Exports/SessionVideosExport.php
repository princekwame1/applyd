<?php

namespace App\Exports;

use App\Models\SessionVideo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SessionVideosExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return SessionVideo::ordered()->get();
    }

    public function headings(): array
    {
        return ['ID', 'Order', 'Title', 'Session', 'Recorded On', 'Status', 'YouTube URL', 'Description', 'Updated At'];
    }

    public function map($video): array
    {
        return [
            $video->id,
            $video->sort_order,
            $video->title,
            $video->session_label,
            $video->recorded_on?->format('Y-m-d'),
            $video->is_published ? 'Published' : 'Hidden',
            $video->watch_url,
            $video->description,
            $video->updated_at->format('Y-m-d H:i'),
        ];
    }
}
