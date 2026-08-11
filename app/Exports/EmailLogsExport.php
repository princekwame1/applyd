<?php

namespace App\Exports;

use App\Models\EmailLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmailLogsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return EmailLog::with('registration')->latest()->get();
    }

    public function headings(): array
    {
        return ['ID', 'Date', 'Name', 'Email', 'Template', 'Subject', 'Status', 'Sent At', 'Retries', 'Last Retry', 'Response'];
    }

    public function map($log): array
    {
        return [
            $log->id,
            $log->created_at?->format('Y-m-d H:i'),
            $log->name ?? $log->registration?->full_name,
            $log->email,
            $log->template_label,
            $log->subject,
            ucfirst($log->status),
            $log->sent_at?->format('Y-m-d H:i'),
            $log->retry_count,
            $log->last_retry_at?->format('Y-m-d H:i'),
            $log->response,
        ];
    }
}
