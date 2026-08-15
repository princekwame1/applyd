<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\QuestionnaireQuestion;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * The fields on one form, in the order the public page asks them. Drag a row
 * to move a question — that order is what participants see.
 */
class QuestionnaireQuestionsTable extends DataTableComponent
{
    use WithSkeletonLoader;

    protected $model = QuestionnaireQuestion::class;

    public int $questionnaireId = 0;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('sort_order', 'asc');
        $this->setPerPageAccepted([25, 50, 100]);
        $this->setPerPage(50);
        $this->setSearchDisabled();
        $this->setBulkActionsDisabled();
        $this->setDefaultReorderSort('sort_order', 'asc');
        $this->setReorderEnabled();

        // `options` and `settings` sit behind label columns, so they need
        // asking for by name or they never reach the model.
        $this->setAdditionalSelects([
            'questionnaire_questions.options',
            'questionnaire_questions.settings',
        ]);
    }

    public function builder(): Builder
    {
        return QuestionnaireQuestion::query()->where('questionnaire_id', $this->questionnaireId);
    }

    /** Reordering is scoped to this form — never touch another one's rows. */
    public function reorder($rows): void
    {
        foreach ($rows as $row) {
            QuestionnaireQuestion::where('id', $row['id'])
                ->where('questionnaire_id', $this->questionnaireId)
                ->update(['sort_order' => (int) $row['sort_order']]);
        }
    }

    public function columns(): array
    {
        return [
            Column::make('Question', 'label')
                ->sortable(),
            Column::make('Key', 'key')
                ->format(fn ($value) => '<code style="font-size:.78rem;">'.e($value).'</code>')
                ->html(),
            Column::make('Type', 'type')
                ->sortable()
                ->format(fn ($value, $row) => e($row->typeName())),
            Column::make('Choices')
                ->label(fn ($row) => $this->choicesCell($row))
                ->html(),
            Column::make('Required', 'is_required')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Yes</span>'
                    : '<span class="badge badge-no">Optional</span>')
                ->html(),
            Column::make('Live', 'is_active')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Live</span>'
                    : '<span class="badge badge-no">Hidden</span>')
                ->html(),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.questionnaires.partials.question-actions', ['id' => $value]))
                ->html(),
        ];
    }

    /** Options for a list question, upload limits for a file one, else a dash. */
    protected function choicesCell(QuestionnaireQuestion $question): string
    {
        if ($question->isFile()) {
            return e($question->fileMimes().' · max '.round($question->fileMaxKb() / 1024, 1).' MB');
        }

        $options = $question->optionList();

        if (! $options) {
            return '<span style="color:var(--ink-soft);">—</span>';
        }

        return e(implode(' · ', array_slice($options, 0, 3)))
            .(count($options) > 3 ? ' <span style="color:var(--ink-soft);">+'.(count($options) - 3).'</span>' : '');
    }
}
