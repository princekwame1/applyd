<?php

namespace App\Livewire;

use App\Models\SmsLog;
use App\Services\SmsNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class SmsLogsTable extends DataTableComponent
{
    use \App\Livewire\Concerns\WithSkeletonLoader;

    protected $model = SmsLog::class;

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
        return SmsLog::query()->with('registration');
    }

    public function columns(): array
    {
        return [
            Column::make('Date', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M d, Y H:i')),
            Column::make('Name', 'registration.full_name')
                ->searchable()
                ->format(fn ($value, $row) => $row->registration?->full_name
                    ?? '<span style="color: var(--ink-soft);">—</span>')
                ->html(),
            Column::make('Phone', 'phone_number')
                ->searchable(),
            Column::make('Message', 'message')
                ->searchable()
                ->format(fn ($value) => e(Str::limit($value, 60))),
            Column::make('Status', 'status')
                ->sortable()
                ->format(fn ($value) => '<span class="status-chip status-'.e($value).'">'.e(ucfirst($value)).'</span>')
                ->html(),
            Column::make('Retries', 'retry_count')
                ->sortable()
                ->format(fn ($value) => '<span style="display:block; text-align:center;">'.(int) $value.'</span>')
                ->html(),
            Column::make('Action', 'id')
                ->format(fn ($value, $row) => view('dashboard.sms-logs.partials.actions', [
                    'id' => $row->id,
                    'status' => $row->status,
                ]))
                ->html(),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status')
                ->options([
                    '' => 'All',
                    'sent' => 'Sent',
                    'failed' => 'Failed',
                    'pending' => 'Pending',
                ])
                ->filter(fn (Builder $builder, string $value) => $builder->where('status', $value)),
        ];
    }

    public function retry(int $id): void
    {
        $log = SmsLog::find($id);

        if (! $log) {
            return;
        }

        $success = (new SmsNotificationService())->send(
            $log->phone_number,
            $log->message,
            $log->registration_id
        );

        if ($success) {
            $log->update([
                'retry_count' => $log->retry_count + 1,
                'last_retry_at' => now(),
            ]);
        }

        $this->js(sprintf(
            "Swal.fire({toast:true,position:'top-end',showConfirmButton:false,timer:3000,timerProgressBar:true,icon:'%s',title:'%s'})",
            $success ? 'success' : 'error',
            $success ? 'SMS retry sent successfully' : 'Failed to retry SMS'
        ));
    }
}
