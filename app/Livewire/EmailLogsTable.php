<?php

namespace App\Livewire;

use App\Models\EmailLog;
use App\Services\EmailNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class EmailLogsTable extends DataTableComponent
{
    use \App\Livewire\Concerns\WithSkeletonLoader;

    protected $model = EmailLog::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([15, 25, 50, 100]);
        $this->setPerPage(15);
    }

    public function builder(): Builder
    {
        return EmailLog::query()->with('registration');
    }

    public function columns(): array
    {
        return [
            Column::make('Date', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M d, Y H:i')),
            Column::make('Name', 'name')
                ->searchable()
                ->format(fn ($value, $row) => e($row->name ?? $row->registration?->full_name ?? '—')),
            Column::make('Email', 'email')
                ->searchable(),
            Column::make('Subject', 'subject')
                ->searchable()
                ->format(fn ($value) => e(Str::limit($value, 55))),
            Column::make('Template', 'template_key')
                ->sortable()
                ->format(fn ($value, $row) => e($row->template_label)),
            Column::make('Status', 'status')
                ->sortable()
                ->format(fn ($value) => '<span class="status-chip status-'.e($value).'">'.e(ucfirst($value)).'</span>')
                ->html(),
            Column::make('Retries', 'retry_count')
                ->sortable()
                ->format(fn ($value) => '<span style="display:block; text-align:center;">'.(int) $value.'</span>')
                ->html(),
            Column::make('Action', 'id')
                ->format(fn ($value, $row) => view('dashboard.email-logs.partials.actions', [
                    'id' => $row->id,
                ]))
                ->html(),
        ];
    }

    public function filters(): array
    {
        $templates = collect(config('email_templates.templates', []))
            ->map(fn ($definition, $key) => $definition['label'] ?? $key)
            ->prepend('All', '')
            ->all();

        return [
            SelectFilter::make('Status')
                ->options([
                    '' => 'All',
                    'sent' => 'Sent',
                    'failed' => 'Failed',
                    'pending' => 'Pending',
                ])
                ->filter(fn (Builder $builder, string $value) => $builder->where('status', $value)),

            SelectFilter::make('Template')
                ->options($templates)
                ->filter(fn (Builder $builder, string $value) => $builder->where('template_key', $value)),
        ];
    }

    public function resend(int $id): void
    {
        $log = EmailLog::find($id);

        if (! $log) {
            return;
        }

        $success = app(EmailNotificationService::class)->resend($log);

        $this->js(sprintf(
            "Swal.fire({toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true,icon:'%s',title:'%s'})",
            $success ? 'success' : 'error',
            $success ? 'Email resent successfully' : 'Failed to resend email'
        ));
    }
}
