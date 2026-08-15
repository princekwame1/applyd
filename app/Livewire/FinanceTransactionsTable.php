<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Support\Finance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class FinanceTransactionsTable extends DataTableComponent
{
    use WithSkeletonLoader;

    protected $model = FinanceTransaction::class;

    /** The window the whole Finance screen is reporting on. */
    public ?string $from = null;

    public ?string $to = null;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('occurred_on', 'desc');
        $this->setPerPageAccepted([10, 25, 50, 100]);
        $this->setPerPage(25);
        $this->setBulkActionsDisabled();

        // `type` decides which column an amount lands in, and it isn't a column
        // of its own — so rappasoft has to be told to select it.
        $this->setAdditionalSelects(['finance_transactions.type']);
    }

    public function builder(): Builder
    {
        // The period comes from the form above the table, not from a filter in
        // it — one control for the window, so the cards, the breakdown, this
        // table and the export can never be showing different things.
        return FinanceTransaction::query()
            ->with('category')
            ->betweenDates($this->from, $this->to);
    }

    public function filters(): array
    {
        $categories = ['' => 'All categories'];

        foreach (FinanceCategory::ordered()->get() as $category) {
            $categories[$category->id] = $category->typeLabel().' — '.$category->name;
        }

        return [
            SelectFilter::make('Type')
                ->options(['' => 'Everything'] + FinanceTransaction::TYPES)
                ->filter(fn (Builder $builder, string $value) => $builder->where('finance_transactions.type', $value)),

            SelectFilter::make('Category')
                ->options($categories)
                ->filter(fn (Builder $builder, string $value) => $builder->where('finance_category_id', $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Date', 'occurred_on')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y')),

            Column::make('Reference', 'reference')
                ->searchable()
                ->format(fn ($value) => '<code style="font-size:.78rem;">'.e($value).'</code>')
                ->html(),

            Column::make('Category', 'finance_category_id')
                ->sortable()
                ->format(fn ($value, $row) => $row->category
                    ? e($row->category->name)
                    : '<span style="color:var(--ink-soft);">Uncategorised</span>')
                ->html(),

            Column::make('From / To', 'party')
                ->searchable()
                ->format(fn ($value) => $value
                    ? e(Str::limit($value, 34))
                    : '<span style="color:var(--ink-soft);">—</span>')
                ->html(),

            // Two money columns rather than one signed figure: it reads like a
            // cash book, and the eye can scan a single side down the page.
            Column::make('In', 'amount')
                ->sortable()
                ->format(fn ($value, $row) => $row->isIncome()
                    ? '<span class="fin-in">'.e(Finance::money($value)).'</span>'
                    : '<span style="color:var(--ink-soft);">—</span>')
                ->html(),

            Column::make('Out')
                ->label(fn ($row) => $row->isIncome()
                    ? '<span style="color:var(--ink-soft);">—</span>'
                    : '<span class="fin-out">'.e(Finance::money($row->amount)).'</span>')
                ->html(),

            Column::make('Docs')
                ->label(fn ($row) => $this->documentsCell($row))
                ->html(),

            Column::make('Note', 'note')
                ->searchable()
                ->format(fn ($value) => $value
                    ? '<span title="'.e($value).'">'.e(Str::limit($value, 28)).'</span>'
                    : '<span style="color:var(--ink-soft);">—</span>')
                ->html(),

            Column::make('Actions', 'id')
                ->format(fn ($value, $row) => view('dashboard.finance.partials.transaction-actions', ['transaction' => $row]))
                ->html(),
        ];
    }

    /**
     * Counted per row rather than with withCount(): rappasoft rebuilds the
     * SELECT list from the real columns, which drops count subqueries.
     */
    protected function documentsCell(FinanceTransaction $transaction): string
    {
        $count = $transaction->documents()->count();

        if (! $count) {
            return '<span style="color:var(--ink-soft);" title="Nothing attached">—</span>';
        }

        return '<span class="fin-docs" title="'.$count.' attached">'
            .'<i class="fa-solid fa-paperclip"></i> '.$count.'</span>';
    }
}
