<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\PlanPurchase;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class PlanPurchasesTable extends DataTableComponent
{
    use WithSkeletonLoader;

    protected $model = PlanPurchase::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);
        $this->setBulkActionsDisabled();
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
        ];
    }
}
