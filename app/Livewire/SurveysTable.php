<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\Survey;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class SurveysTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = Survey::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('sort_order', 'asc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);
        $this->setDefaultReorderSort('sort_order', 'asc');
        $this->setReorderEnabled();
    }

    public function reorder($rows): void
    {
        foreach ($rows as $row) {
            Survey::where('id', $row['id'])->update(['sort_order' => (int) $row['sort_order']]);
        }
    }

    public function columns(): array
    {
        return [
            Column::make('Survey', 'name')
                ->sortable()
                ->searchable(),
            Column::make('Link', 'slug')
                ->searchable()
                ->format(fn ($value) => '<code style="font-size:.78rem;">/check-in/'.e($value).'</code>')
                ->html(),
            // Counted per row rather than with withCount(): rappasoft rebuilds
            // the SELECT list from the real columns, which drops count subqueries.
            // A handful of surveys makes that a non-issue.
            Column::make('Questions')
                ->label(fn ($row) => $row->questions()->where('active', true)->count()
                    .' of '.$row->questions()->count().' live'),
            Column::make('Responses')
                ->label(fn ($row) => number_format($row->responses()->count())),
            Column::make('Status', 'is_active')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Open</span>'
                    : '<span class="badge badge-no">Closed</span>')
                ->html(),
            Column::make('Actions', 'id')
                ->format(fn ($value, $row) => view('dashboard.surveys.partials.survey-actions', ['survey' => $row]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteNoun(): string
    {
        return 'survey';
    }

    protected function deleteWarning(): string
    {
        return 'Its questions go too. Only possible while it has no responses.';
    }

    /** Mirrors SurveyManagerController::destroy — answers outlive the form. */
    protected function deleteBlockedReason(Model $row): ?string
    {
        $count = $row->responses()->count();

        return $count
            ? $row->name.' has '.$count.' response(s) — switch it off instead'
            : null;
    }

    /**
     * Done here rather than left to the FK: SQLite ignores foreign keys added
     * by a later ALTER TABLE, so the cascade is MySQL-only.
     */
    protected function beforeDelete(Model $row): void
    {
        $row->questions()->delete();
    }
}
