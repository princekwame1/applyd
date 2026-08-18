<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\QuestionnaireQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * The fields on one form, in the order the public page asks them. Drag a row
 * to move a question — that order is what participants see.
 */
class QuestionnaireQuestionsTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = QuestionnaireQuestion::class;

    public int $questionnaireId = 0;

    /** @var Collection<string, QuestionnaireQuestion>|null */
    protected ?Collection $questionsByKey = null;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('sort_order', 'asc');
        $this->setPerPageAccepted([25, 50, 100]);
        $this->setPerPage(50);
        $this->setSearchDisabled();
        $this->setDefaultReorderSort('sort_order', 'asc');
        $this->setReorderEnabled();

        // `options` and `settings` sit behind label columns, so they need
        // asking for by name or they never reach the model.
        $this->setAdditionalSelects([
            'questionnaire_questions.options',
            'questionnaire_questions.settings',
            'questionnaire_questions.visible_when',
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
            Column::make('Asked when')
                ->label(fn ($row) => $this->conditionCell($row))
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

    /** Reads the condition back as a sentence, or "Always" when there isn't one. */
    protected function conditionCell(QuestionnaireQuestion $question): string
    {
        $rule = $question->condition();

        if (! $rule) {
            return '<span style="color:var(--ink-soft);">Always</span>';
        }

        $controller = $this->questionsByKey()->get($rule['key']);

        return '<span style="color:var(--brand); font-size:.82rem;">'
            .e($question->conditionSummary($controller) ?? '')
            .'</span>';
    }

    /** This form's questions keyed for label lookups, resolved once per render. */
    protected function questionsByKey(): Collection
    {
        return $this->questionsByKey ??= QuestionnaireQuestion::where('questionnaire_id', $this->questionnaireId)
            ->get()
            ->keyBy('key');
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

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteNoun(): string
    {
        return 'question';
    }

    protected function deleteLabel(Model $row): string
    {
        return Str::limit($row->label, 40);
    }

    protected function deleteWarning(): string
    {
        return 'Answers already collected for it are kept.';
    }
}
