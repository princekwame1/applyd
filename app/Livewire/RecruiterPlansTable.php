<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\RecruiterPlan;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RecruiterPlansTable extends DataTableComponent
{
    use WithSkeletonLoader;

    protected $model = RecruiterPlan::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('sort_order', 'asc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);
        $this->setBulkActionsDisabled();
        $this->setDefaultReorderSort('sort_order', 'asc');
        $this->setReorderEnabled();
    }

    public function reorder($rows): void
    {
        foreach ($rows as $row) {
            RecruiterPlan::where('id', $row['id'])->update(['sort_order' => (int) $row['sort_order']]);
        }
    }

    public function columns(): array
    {
        return [
            Column::make('Plan', 'name')
                ->sortable()
                ->searchable(),
            Column::make('Price', 'price')
                ->sortable()
                ->format(fn ($value, $row) => e($row->price_label)),
            Column::make('CV unlocks', 'cv_credits')
                ->sortable()
                ->format(fn ($value) => number_format($value)),
            // Counted per row: rappasoft rebuilds the SELECT list from real
            // columns, which drops withCount() subqueries.
            Column::make('Sold')
                ->label(fn ($row) => number_format($row->purchases()->where('status', 'paid')->count())),
            Column::make('Status', 'is_active')
                ->sortable()
                ->format(fn ($value, $row) => ($value
                        ? '<span class="badge badge-yes">On sale</span>'
                        : '<span class="badge badge-no">Hidden</span>')
                    .($row->is_featured ? ' <span class="badge badge-yes">Featured</span>' : ''))
                ->html(),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.recruiter-plans.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }
}
