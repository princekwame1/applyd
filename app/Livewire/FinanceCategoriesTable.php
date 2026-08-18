<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Support\Finance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class FinanceCategoriesTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = FinanceCategory::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('type', 'asc');
        $this->setPerPageAccepted([25, 50, 100]);
        $this->setPerPage(50);
        $this->setDefaultReorderSort('sort_order', 'asc');
        $this->setReorderEnabled();
    }

    public function reorder($rows): void
    {
        foreach ($rows as $row) {
            FinanceCategory::where('id', $row['id'])->update(['sort_order' => (int) $row['sort_order']]);
        }
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Side')
                ->options(['' => 'Both'] + FinanceTransaction::TYPES)
                ->filter(fn (Builder $builder, string $value) => $builder->where('type', $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Category', 'name')
                ->sortable()
                ->searchable(),

            Column::make('Side', 'type')
                ->sortable()
                ->format(fn ($value) => $value === FinanceTransaction::INCOME
                    ? '<span class="badge badge-yes">Money in</span>'
                    : '<span class="badge badge-out">Money out</span>')
                ->html(),

            Column::make('Entries')
                ->label(fn ($row) => number_format($row->transactions()->count())),

            Column::make('Total')
                ->label(fn ($row) => Finance::money($row->transactions()->sum('amount'))),

            Column::make('Note', 'note')
                ->format(fn ($value) => $value
                    ? e($value)
                    : '<span style="color:var(--ink-soft);">—</span>')
                ->html(),

            Column::make('In use', 'is_active')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Yes</span>'
                    : '<span class="badge badge-no">Retired</span>')
                ->html(),

            Column::make('Actions', 'id')
                ->format(fn ($value, $row) => view('dashboard.finance.partials.category-actions', ['category' => $row]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteAbility(): ?string
    {
        return 'manage finance';
    }

    protected function deleteNoun(): string
    {
        return 'category';
    }

    protected function deleteWarning(): string
    {
        return 'Only possible for a heading nothing is filed under.';
    }

    /** Mirrors FinanceCategoryController::destroy — history keeps its heading. */
    protected function deleteBlockedReason(Model $row): ?string
    {
        $count = $row->transactions()->count();

        return $count
            ? $row->name.' is used by '.$count.' entries — switch it off instead'
            : null;
    }
}
