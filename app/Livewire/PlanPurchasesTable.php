<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\PlanPurchase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class PlanPurchasesTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = PlanPurchase::class;

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
        return PlanPurchase::query()->with('company');
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status')
                ->options(['' => 'All', 'paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed'])
                ->filter(fn (Builder $builder, string $value) => $builder->where('status', $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Company', 'company_id')
                ->sortable()
                ->format(fn ($value, $row) => e($row->company?->name ?? '—')),
            Column::make('Plan', 'plan_name')
                ->sortable()
                ->searchable(),
            Column::make('Credits', 'credits')
                ->sortable()
                ->format(fn ($value) => number_format($value)),
            Column::make('Amount', 'amount')
                ->sortable()
                ->format(fn ($value, $row) => e($row->amount_label)),
            Column::make('Reference', 'reference')
                ->searchable()
                ->format(fn ($value) => '<code style="font-size:.78rem;">'.e($value).'</code>')
                ->html(),
            Column::make('Status', 'status')
                ->sortable()
                ->format(fn ($value) => match ($value) {
                    'paid' => '<span class="badge badge-yes">Paid</span>',
                    'failed' => '<span class="badge badge-no">Failed</span>',
                    default => '<span class="badge badge-no">Pending</span>',
                })
                ->html(),
            Column::make('Date', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y g:ia')),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.partials.row-delete', ['id' => $value]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteNoun(): string
    {
        return 'purchase';
    }

    protected function deleteLabel(Model $row): string
    {
        return $row->plan_name.' ('.$row->reference.')';
    }

    protected function deleteWarning(): string
    {
        return 'Credits already bought are counted from these rows, so deleting a paid purchase takes those credits away from the company.';
    }

    /**
     * A settled purchase is what a company's credit balance is counted from
     * (`Company::creditsBought()`), and credits it has already spent cannot be
     * un-spent. Deleting one would leave the balance negative and the unlocks
     * paid for by nothing.
     */
    protected function deleteBlockedReason(Model $row): ?string
    {
        if ($row->status !== 'paid') {
            return null;
        }

        $company = $row->company;
        $spent = $company?->creditsUsed() ?? 0;
        $remainingIfDeleted = ($company?->creditsBought() ?? 0) - (int) $row->credits;

        return $remainingIfDeleted < $spent
            ? $row->reference.' is paid for and those credits are already spent'
            : null;
    }
}
