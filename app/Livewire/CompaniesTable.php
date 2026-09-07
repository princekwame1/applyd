<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\Company;
use App\Services\JobBoardNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class CompaniesTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = Company::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        // Pending first: this table is a work queue before it is a list.
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);

        // Read by label columns, which rappasoft would otherwise not select.
        $this->setAdditionalSelects(['companies.ghana_card', 'companies.user_id', 'companies.review_note']);
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Verification')
                ->options([
                    '' => 'All',
                    Company::PENDING => 'Pending review',
                    Company::APPROVED => 'Verified',
                    Company::REJECTED => 'Rejected',
                ])
                ->filter(fn (Builder $builder, string $value) => $builder->where('companies.status', $value)),
            SelectFilter::make('Ghana Card')
                ->options(['' => 'All', 'yes' => 'On file', 'no' => 'Missing'])
                ->filter(fn (Builder $builder, string $value) => $value === 'yes'
                    ? $builder->whereNotNull('companies.ghana_card')
                    : $builder->whereNull('companies.ghana_card')),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Company', 'name')
                ->sortable()
                ->searchable(),
            Column::make('Contact', 'user.name')
                ->sortable()
                ->searchable(),
            Column::make('Ghana Card')
                ->label(fn ($row) => $row->ghana_card
                    ? '<code>'.e($row->ghana_card).'</code>'
                    : '<span style="color:var(--ink-soft);">Not provided</span>')
                ->html(),
            Column::make('Jobs')
                ->label(fn ($row) => number_format($row->openings()->count())),
            Column::make('Status', 'status')
                ->sortable()
                ->format(fn ($value) => match ($value) {
                    Company::APPROVED => '<span class="badge badge-yes">Verified</span>',
                    Company::REJECTED => '<span class="badge badge-no">Rejected</span>',
                    default => '<span class="status-chip status-pending">Pending</span>',
                })
                ->html(),
            Column::make('Registered', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y')),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.companies.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return [
            'approveSelected' => 'Verify selected',
            'deleteSelected' => 'Delete selected',
        ];
    }

    /**
     * Bulk verify. Rejection is deliberately not here — it needs a reason
     * typed against a specific company, and one reason pasted across a
     * batch would be worse than no reason at all.
     */
    public function approveSelected(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['admin', 'super']), 403);

        $notifier = app(JobBoardNotificationService::class);
        $approved = 0;
        $skipped = 0;

        foreach (Company::whereIn('id', $this->getSelected())->get() as $company) {
            if ($company->approve(auth()->user())) {
                $notifier->companyApproved($company);
                $approved++;
            } else {
                $skipped++;
            }
        }

        $this->clearSelected();

        $message = $approved.' '.str('company')->plural($approved).' verified and emailed.';

        if ($skipped) {
            $message .= ' '.$skipped.' already verified, so nothing was sent.';
        }

        session()->flash('status', $message);
    }

    protected function deleteNoun(): string
    {
        return 'company';
    }

    protected function deleteLabel(Model $row): string
    {
        return $row->name;
    }

    protected function deleteWarning(): string
    {
        return 'Their job postings, the applications sent to them and their sign-in account all go with it. Credits they bought are not refunded.';
    }

    protected function beforeDelete(Model $row): void
    {
        if ($row->logo) {
            Storage::disk('public')->delete($row->logo);
        }

        // The account behind the company is theirs alone — leaving it would
        // strand a login with the company role and nothing to sign in to.
        $row->user?->delete();
    }
}
