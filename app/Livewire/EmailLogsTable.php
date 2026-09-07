<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\EmailLog;
use App\Services\EmailNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class EmailLogsTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

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
            // Why a send failed. It was only ever written to the row and to
            // laravel.log before, so the one question this screen exists to
            // answer — "what went wrong?" — could not be answered on it.
            // Quiet for a successful send: "Accepted by mailgun mailer" on
            // every row is noise that hides the one line that matters.
            Column::make('Reason', 'response')
                ->format(function ($value, $row) {
                    if ($row->status === 'sent' || ! filled($value)) {
                        return '<span style="color:var(--ink-soft);">—</span>';
                    }

                    return '<span title="'.e($value).'" style="font-size:.82rem; color:var(--danger);">'
                        .e(Str::limit($value, 70)).'</span>';
                })
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
                    'queued' => 'Queued',
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
            $success ? 'Email '.EmailNotificationService::verb() : 'Failed to resend email'
        ));
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteNoun(): string
    {
        return 'log entry';
    }

    protected function deleteLabel(Model $row): string
    {
        return 'the email to '.$row->email;
    }

    protected function deleteWarning(): string
    {
        return 'The record of what was sent goes with it. Already delivered mail is unaffected — but deleting a Queued row cancels it before it leaves.';
    }
}
