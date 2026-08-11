<?php

namespace App\Livewire;

use App\Http\Controllers\Dashboard\BulkEmailController;
use App\Models\Registration;
use App\Models\SmsLog;
use App\Models\Tool;
use App\Services\EmailNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\MultiSelectFilter;

class RegistrationsTable extends DataTableComponent
{
    use \App\Livewire\Concerns\WithSkeletonLoader;

    protected $model = Registration::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([15, 25, 50, 100]);
        $this->setPerPage(15);
    }

    public function filters(): array
    {
        return [
            MultiSelectFilter::make('Tools')
                ->options(Tool::ordered()->pluck('name', 'name')->all())
                // `tools` is a JSON array, so match on containment rather than
                // equality. Multiple picks are OR-ed: "anyone who chose any of
                // these", which is what you want when sizing a session.
                ->filter(function (Builder $builder, array $values) {
                    $builder->where(function (Builder $query) use ($values) {
                        foreach ($values as $value) {
                            $query->orWhereJsonContains('tools', $value);
                        }
                    });
                }),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'full_name')
                ->sortable()
                ->searchable(),
            Column::make('Location', 'country')
                ->sortable()
                ->searchable()
                ->format(fn ($value, $row) => $value.' — '.$row->city),
            Column::make('City', 'city')
                ->searchable()
                ->hideIf(true),
            Column::make('Email', 'email')
                ->sortable()
                ->searchable(),
            Column::make('Phone', 'phone')
                ->searchable()
                ->format(fn ($value, $row) => $row->phone_country_code.' '.$value),
            Column::make('Tools', 'tools')
                // Names, not a bare count — once you filter by tool you need to
                // see which ones matched.
                ->format(function ($value) {
                    $tools = $value ?? [];

                    if (! $tools) {
                        return '<span style="color:var(--ink-soft);">None</span>';
                    }

                    $shown = implode(', ', array_map('e', array_slice($tools, 0, 3)));
                    $extra = count($tools) - 3;

                    return $extra > 0
                        ? $shown.' <span style="color:var(--ink-soft);">+'.$extra.' more</span>'
                        : $shown;
                })
                ->html(),
            Column::make('Opt-in', 'marketing_opt_in')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Yes</span>'
                    : '<span class="badge badge-no">No</span>')
                ->html(),
            Column::make('Registered', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y g:ia')),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.registrations.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        $actions = [
            'composeSelected' => 'Send email to selected…',
            'resendSelected' => 'Resend confirmation email',
        ];

        if ($this->canDelete()) {
            $actions['deleteSelected'] = 'Delete selected';
        }

        return $actions;
    }

    protected function canDelete(): bool
    {
        return (bool) auth()->user()?->can('manage registrations');
    }

    /**
     * Ask first. The row is gone for good — there are no soft deletes on
     * registrations.
     */
    public function deleteRow(int $id): void
    {
        $registration = Registration::find($id);

        if (! $registration) {
            return;
        }

        $this->confirm(
            'Delete '.$registration->full_name.'?',
            'Their registration is removed permanently. Delivery history is kept.',
            'performDelete('.$id.')'
        );
    }

    public function deleteSelected(): void
    {
        $count = count($this->getSelected());

        if (! $count) {
            $this->toast(false, 'Tick at least one registrant first');

            return;
        }

        $this->confirm(
            'Delete '.$count.' '.($count === 1 ? 'registration' : 'registrations').'?',
            'They are removed permanently. Delivery history is kept.',
            'performDeleteSelected'
        );
    }

    public function performDelete(int $id): void
    {
        abort_unless($this->canDelete(), 403);

        $registration = Registration::find($id);

        if (! $registration) {
            return;
        }

        $name = $registration->full_name;
        $this->detachLogs([$id]);
        $registration->delete();

        $this->toast(true, $name.' deleted');
    }

    public function performDeleteSelected(): void
    {
        abort_unless($this->canDelete(), 403);

        $ids = array_map('intval', $this->getSelected());
        $this->clearSelected();

        if (! $ids) {
            return;
        }

        $this->detachLogs($ids);
        $deleted = Registration::whereIn('id', $ids)->delete();

        $this->toast(true, $deleted.' '.($deleted === 1 ? 'registration' : 'registrations').' deleted');
    }

    /**
     * `sms_logs.registration_id` is ON DELETE CASCADE, so deleting a registrant
     * would wipe their SMS delivery history — while `email_logs` is nullOnDelete
     * and keeps it. Detaching first makes both behave the same: the person goes,
     * the audit trail of what we sent them stays.
     */
    protected function detachLogs(array $ids): void
    {
        SmsLog::whereIn('registration_id', $ids)->update(['registration_id' => null]);
    }

    /**
     * SweetAlert2 confirm that calls back into the component on "yes" — the
     * project's confirmation convention, adapted for a Livewire action where
     * there is no form to hang `data-confirm` on.
     */
    protected function confirm(string $title, string $text, string $onConfirm): void
    {
        $this->js(sprintf(
            "Swal.fire({title:'%s',text:'%s',icon:'warning',showCancelButton:true,confirmButtonColor:'#c73a41',cancelButtonColor:'#5f605f',confirmButtonText:'Yes, delete',cancelButtonText:'Cancel'}).then((r) => { if (r.isConfirmed) { \$wire.%s } })",
            addslashes($title),
            addslashes($text),
            $onConfirm,
        ));
    }

    /**
     * Hand the ticked registrants to the compose screen, where the admin
     * writes a one-off message instead of re-sending a stored template.
     */
    public function composeSelected()
    {
        $ids = array_map('intval', $this->getSelected());

        if (! $ids) {
            $this->toast(false, 'Tick at least one registrant first');

            return null;
        }

        session()->put(BulkEmailController::SESSION_KEY, $ids);
        $this->clearSelected();

        return $this->redirect(route('dashboard.registrations.bulk-email'), navigate: false);
    }

    /**
     * Re-send the confirmation email to one registrant, re-rendered from the
     * current template so they always get the latest wording.
     */
    public function resendEmail(int $id): void
    {
        $registration = Registration::find($id);

        if (! $registration) {
            return;
        }

        $success = app(EmailNotificationService::class)->sendRegistrationConfirmation($registration);

        $this->toast(
            $success,
            $success ? 'Email sent to '.$registration->email : 'Failed to send email — see Email Delivery'
        );
    }

    public function resendSelected(): void
    {
        $registrations = Registration::whereIn('id', $this->getSelected())->get();
        $this->clearSelected();

        $emails = app(EmailNotificationService::class);
        $sent = 0;

        foreach ($registrations as $registration) {
            if ($emails->sendRegistrationConfirmation($registration)) {
                $sent++;
            }
        }

        $failed = $registrations->count() - $sent;

        $this->toast(
            $failed === 0 && $sent > 0,
            $failed === 0
                ? $sent.' email'.($sent === 1 ? '' : 's').' sent'
                : $sent.' sent, '.$failed.' failed — see Email Delivery'
        );
    }

    protected function toast(bool $success, string $message): void
    {
        $this->js(sprintf(
            "Swal.fire({toast:true,position:'top-end',showConfirmButton:false,timer:3500,timerProgressBar:true,icon:'%s',title:'%s'})",
            $success ? 'success' : 'error',
            addslashes($message)
        ));
    }
}
