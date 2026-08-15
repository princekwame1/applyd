<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\Questionnaire;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class QuestionnairesTable extends DataTableComponent
{
    use WithSkeletonLoader;

    protected $model = Questionnaire::class;

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

        // The status badge asks the model whether it's open, which reads the
        // window and the cap — none of which sit behind a Column, so rappasoft
        // would otherwise leave them out of the SELECT.
        $this->setAdditionalSelects([
            'questionnaires.opens_at',
            'questionnaires.closes_at',
            'questionnaires.response_limit',
        ]);
    }

    public function reorder($rows): void
    {
        foreach ($rows as $row) {
            Questionnaire::where('id', $row['id'])->update(['sort_order' => (int) $row['sort_order']]);
        }
    }

    public function columns(): array
    {
        return [
            Column::make('Form', 'title')
                ->sortable()
                ->searchable(),
            Column::make('Link', 'slug')
                ->searchable()
                ->format(fn ($value) => '<code style="font-size:.78rem;">/forms/'.e($value).'</code>')
                ->html(),
            // Counted per row rather than with withCount(): rappasoft rebuilds
            // the SELECT list from the real columns, which drops count
            // subqueries. A handful of forms makes that a non-issue.
            Column::make('Questions')
                ->label(fn ($row) => $row->questions()->where('is_active', true)->count()
                    .' of '.$row->questions()->count().' live'),
            Column::make('Responses')
                ->label(fn ($row) => number_format($row->responses()->count())),
            Column::make('Status', 'is_published')
                ->sortable()
                ->format(fn ($value, $row) => $this->statusBadge($row))
                ->html(),
            Column::make('Actions', 'id')
                ->format(fn ($value, $row) => view('dashboard.questionnaires.partials.questionnaire-actions', ['questionnaire' => $row]))
                ->html(),
        ];
    }

    /**
     * "Published" isn't the same as "taking answers" — a form can be live and
     * still be outside its window or over its cap, so the badge says which.
     */
    protected function statusBadge(Questionnaire $questionnaire): string
    {
        if (! $questionnaire->is_published) {
            return '<span class="badge badge-no">Draft</span>';
        }

        if ($reason = $questionnaire->closedReason()) {
            return '<span class="badge badge-no" title="'.e($reason).'">Closed</span>';
        }

        return '<span class="badge badge-yes">Open</span>';
    }
}
