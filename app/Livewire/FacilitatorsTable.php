<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\User;
use App\Services\FacilitatorAccountService;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

/**
 * Who can teach, and whether they can actually get in.
 *
 * There is no delete here on purpose: facilitator access is *revoked* (the
 * role comes off, the account and everything they taught stays), which is a
 * plain form on the row rather than a Livewire delete action. Deleting an
 * account outright is still /dashboard/users.
 */
class FacilitatorsTable extends DataTableComponent
{
    use WithSkeletonLoader;

    protected $model = User::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('name', 'asc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);

        // The row actions and the access badge read fields that sit behind no
        // column of their own.
        $this->setAdditionalSelects(['users.must_change_password', 'users.credentials_sent_at']);
    }

    public function builder(): Builder
    {
        return User::query()->facilitators();
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Portal access')
                ->options([
                    '' => 'All',
                    'pending' => 'Never sent',
                    'sent' => 'Sent, not signed in',
                    'active' => 'Signed in, own password',
                ])
                ->filter(fn (Builder $builder, string $value) => match ($value) {
                    'pending' => $builder->whereNull('credentials_sent_at'),
                    'sent' => $builder->whereNotNull('credentials_sent_at')->where('must_change_password', true),
                    'active' => $builder->whereNotNull('credentials_sent_at')->where('must_change_password', false),
                    default => $builder,
                }),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Name', 'name')
                ->sortable()
                ->searchable(),
            Column::make('Email', 'email')
                ->sortable()
                ->searchable(),
            Column::make('Phone', 'phone')
                ->searchable()
                ->format(fn ($value) => e($value ?: '—')),
            Column::make('Portal access', 'credentials_sent_at')
                ->sortable()
                ->format(fn ($value, $row) => match ($row->access_state) {
                    'active' => '<span class="badge badge-yes">Signed in</span>',
                    'sent' => '<span class="badge badge-no">Waiting to sign in</span>',
                    default => '<span class="badge badge-no">Never sent</span>',
                })
                ->html(),
            Column::make('Login sent', 'credentials_sent_at')
                ->sortable()
                ->format(fn ($value) => e($value?->format('M j, Y') ?? '—')),
            Column::make('Added', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y')),
            Column::make('Actions', 'id')
                ->format(fn ($value, $row) => view('dashboard.facilitators.partials.actions', ['facilitator' => $row]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return ['sendCredentialsSelected' => 'Send login details to selected'];
    }

    /**
     * Ask first: a resend regenerates the temporary password for anyone still
     * on the one we issued, so their previous one stops working. Worth a
     * sentence before it happens to a dozen people at once.
     */
    public function sendCredentialsSelected(): void
    {
        $count = count($this->getSelected());

        if (! $count) {
            $this->toast(false, 'Tick at least one facilitator first');

            return;
        }

        $this->confirm(
            'Send login details to '.$count.' '.($count === 1 ? 'facilitator' : 'facilitators').'?',
            'Anyone who has not yet set their own password gets a new temporary one, and their previous password stops working.',
            'performSendCredentialsSelected',
        );
    }

    public function performSendCredentialsSelected(): void
    {
        // Livewire methods are callable over HTTP by anyone who can reach the
        // component, so the gate is here, not only on the page it renders on.
        abort_unless($this->canManage(), 403);

        $ids = array_map('intval', $this->getSelected());
        $this->clearSelected();

        if (! $ids) {
            return;
        }

        $service = app(FacilitatorAccountService::class);
        $sent = 0;
        $skipped = 0;

        foreach (User::facilitators()->whereIn('id', $ids)->get() as $facilitator) {
            try {
                $service->resendCredentials($facilitator);
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $skipped++;
            }
        }

        $message = $sent.' '.($sent === 1 ? 'facilitator' : 'facilitators').' sent their login details';

        if ($skipped) {
            // Never let a silent drop read as a delivery.
            $message .= ' · '.$skipped.' failed, see Email Delivery';
        }

        $this->toast($sent > 0, $sent > 0 ? $message : 'Nothing sent — check Email Delivery for why');
    }

    protected function canManage(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['admin', 'super']);
    }

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
            addslashes($message),
        ));
    }
}
