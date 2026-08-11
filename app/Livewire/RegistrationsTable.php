<?php

namespace App\Livewire;

use App\Http\Controllers\Dashboard\BulkEmailController;
use App\Models\Registration;
use App\Services\EmailNotificationService;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

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
                ->format(fn ($value) => count($value ?? []).' selected'),
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
        return [
            'composeSelected' => 'Send email to selected…',
            'resendSelected' => 'Resend confirmation email',
        ];
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
