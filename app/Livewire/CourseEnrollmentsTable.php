<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class CourseEnrollmentsTable extends DataTableComponent
{
    use WithSkeletonLoader;

    protected $model = CourseEnrollment::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);
    }

    public function builder(): Builder
    {
        return CourseEnrollment::query()->with('course');
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status')
                ->options(['' => 'All', 'paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed'])
                ->filter(fn (Builder $b, string $v) => $b->where('status', $v)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Date', 'created_at')
                ->sortable()
                ->format(fn ($value, $row) => e($row->created_at->format('M j, Y g:ia'))),
            Column::make('Name', 'name')->sortable()->searchable(),
            Column::make('Email', 'email')->searchable(),
            Column::make('Phone', 'phone')->searchable(),
            Column::make('Course', 'course_id')
                ->format(fn ($value, $row) => e($row->course?->title ?? '—')),
            Column::make('Attendance', 'attendance_type')
                ->format(fn ($value, $row) => e($row->attendance_label)),
            Column::make('Amount', 'amount')
                ->sortable()
                ->format(fn ($value, $row) => e($row->amount_label)),
            Column::make('Status', 'status')
                ->sortable()
                ->format(fn ($value) => match ($value) {
                    'paid' => '<span class="badge badge-yes">Paid</span>',
                    'failed' => '<span class="badge" style="background:#fef2f2;color:var(--danger);">Failed</span>',
                    default => '<span class="badge badge-no">Pending</span>',
                })
                ->html(),
            Column::make('Serial No', 'serial_no')
                ->searchable()
                ->format(fn ($value) => $value ? e($value) : '<span style="color:var(--ink-soft);">—</span>')
                ->html(),
            Column::make('PIN', 'pin')
                ->format(fn ($value) => $value ? e($value) : '<span style="color:var(--ink-soft);">—</span>')
                ->html(),
            Column::make('Tuition', 'tuition_status')
                ->format(fn ($value, $row) => match ($value) {
                    'paid' => '<span class="badge badge-yes">Paid</span>',
                    'partial' => '<span class="badge" style="background:#fef3c7;color:#92400e;">50% ('.Course::money((float) $row->tuition_amount).')</span>',
                    'pending' => '<span class="badge badge-no">Pending</span>',
                    default => '<span class="badge badge-no">Unpaid</span>',
                })
                ->html(),
            Column::make('Application', 'completed_at')
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Completed</span>'
                    : '<span class="badge badge-no">Incomplete</span>')
                ->html(),
            Column::make('Student ID', 'student_id')
                ->sortable()
                ->searchable()
                ->format(fn ($value) => $value
                    ? '<code style="font-size:.78rem;">'.e($value).'</code>'
                    : '<span style="color:var(--ink-soft);">not issued</span>')
                ->html(),
            Column::make('Login sent', 'credentials_sent_at')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span title="'.e($value->format('M j, Y g:ia')).'">'.e($value->diffForHumans()).'</span>'
                    : '<span style="color:var(--ink-soft);">—</span>')
                ->html(),
            Column::make('Reference', 'reference')->searchable(),
            Column::make('Actions', 'id')
                ->format(fn ($value, $row) => view('dashboard.partials.enrollment-actions', ['enrollment' => $row]))
                ->html(),
        ];
    }
}
