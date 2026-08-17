<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Services\StudentAccountService;
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

            // The "who still needs their login?" question, which is the whole
            // reason someone opens this screen to resend.
            SelectFilter::make('Login details')
                ->options([
                    '' => 'All',
                    'sent' => 'Already sent',
                    'pending' => 'Not sent yet',
                    'ready' => 'Ready to send (registration complete)',
                ])
                ->filter(fn (Builder $b, string $v) => match ($v) {
                    'sent' => $b->whereNotNull('credentials_sent_at'),
                    'pending' => $b->whereNull('credentials_sent_at'),
                    'ready' => $b->whereNotNull('completed_at')->whereNull('credentials_sent_at'),
                    default => $b,
                }),
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

    public function bulkActions(): array
    {
        return [
            'sendCredentialsSelected' => 'Send login details to selected',
        ];
    }

    /**
     * Ask before sending: a resend regenerates the temporary password for
     * anyone still on the one we issued, so the previous one stops working.
     * That is worth a sentence before it happens to thirty people at once.
     */
    public function sendCredentialsSelected(): void
    {
        $count = count($this->getSelected());

        if (! $count) {
            $this->toast(false, 'Tick at least one student first');

            return;
        }

        $this->confirm(
            'Send login details to '.$count.' '.($count === 1 ? 'student' : 'students').'?',
            'Anyone who has not yet set their own password gets a new temporary one, and their previous password stops working.',
            'performSendCredentialsSelected',
        );
    }

    public function performSendCredentialsSelected(): void
    {
        // Livewire methods are publicly callable, so the gate is here rather
        // than only on the page this table is rendered on.
        abort_unless($this->canManage(), 403);

        $ids = array_map('intval', $this->getSelected());
        $this->clearSelected();

        if (! $ids) {
            return;
        }

        $service = app(StudentAccountService::class);
        $sent = 0;
        $skipped = 0;

        foreach (CourseEnrollment::whereIn('id', $ids)->get() as $enrollment) {
            // A student ID says "enrolled", so it only goes out once the
            // registration is actually finished.
            if (! $enrollment->is_completed) {
                $skipped++;

                continue;
            }

            try {
                $service->resendCredentials($enrollment);
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $skipped++;
            }
        }

        $message = $sent.' '.($sent === 1 ? 'student' : 'students').' sent their login details';

        if ($skipped) {
            // Never let a silent drop look like a delivery.
            $message .= ' · '.$skipped.' skipped (registration not complete)';
        }

        $this->toast($sent > 0, $sent > 0 ? $message : 'Nothing sent — '.$skipped.' skipped (registration not complete)');
    }

    protected function canManage(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['admin', 'super']);
    }

    /**
     * SweetAlert2 confirm that calls back into the component on "yes" — the
     * project's confirmation convention, adapted for a Livewire action where
     * there is no form to hang `data-confirm` on.
     */
    protected function confirm(string $title, string $text, string $onConfirm): void
    {
        $this->js(sprintf(
            "Swal.fire({title:'%s',text:'%s',icon:'warning',showCancelButton:true,confirmButtonColor:'#c73a41',cancelButtonColor:'#5f605f',confirmButtonText:'Yes, send',cancelButtonText:'Cancel'}).then((r) => { if (r.isConfirmed) { \$wire.%s } })",
            addslashes($title),
            addslashes($text),
            $onConfirm,
        ));
    }

    protected function toast(bool $success, string $message): void
    {
        $this->js(sprintf(
            "Swal.fire({toast:true,position:'top-end',showConfirmButton:false,timer:4500,timerProgressBar:true,icon:'%s',title:'%s'})",
            $success ? 'success' : 'error',
            addslashes($message)
        ));
    }
}
