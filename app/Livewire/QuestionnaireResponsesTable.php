<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * Raw submissions, one row per response and one column per question.
 */
class QuestionnaireResponsesTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = QuestionnaireResponse::class;

    public int $questionnaireId = 0;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(10);
        $this->setSearchDisabled();

        // The answer columns are label columns, and rappasoft only SELECTs the
        // fields behind real columns — without this the model arrives with no
        // `answers` and every cell renders empty.
        $this->setAdditionalSelects(['questionnaire_responses.answers']);
    }

    public function builder(): Builder
    {
        return QuestionnaireResponse::query()->where('questionnaire_id', $this->questionnaireId);
    }

    public function columns(): array
    {
        $columns = [
            Column::make('Submitted', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y g:ia')),
            Column::make('Reference', 'reference')
                ->format(fn ($value) => '<code style="font-size:.78rem;">'.e($value).'</code>')
                ->html(),
        ];

        // One column per live question. They're label columns: the answers sit
        // inside a JSON map, so there is no real DB column to sort or search on.
        foreach ($this->questions() as $question) {
            $columns[] = Column::make(Str::limit($question->label, 30))
                ->label(fn ($row) => $this->renderAnswer($question, $row))
                ->html();
        }

        $columns[] = Column::make('Actions', 'id')
            ->format(fn ($value) => view('dashboard.questionnaires.partials.response-actions', ['id' => $value]))
            ->html();

        return $columns;
    }

    /** @return Collection<int, QuestionnaireQuestion> */
    protected function questions(): Collection
    {
        return QuestionnaireQuestion::where('questionnaire_id', $this->questionnaireId)->active()->ordered()->get();
    }

    protected function renderAnswer(QuestionnaireQuestion $question, QuestionnaireResponse $response): string
    {
        $value = $question->formatAnswer($response->answer($question->key));

        if ($value === '') {
            return '<span style="color:var(--ink-soft);">—</span>';
        }

        return '<span title="'.e($value).'">'.e(Str::limit($value, 60)).'</span>';
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteNoun(): string
    {
        return 'response';
    }

    protected function deleteLabel(Model $row): string
    {
        return 'response '.$row->reference;
    }

    protected function deleteWarning(): string
    {
        return 'Any file uploaded with it is deleted from the server too.';
    }

    /** The rows cascade; the uploads on the private disk do not. */
    protected function beforeDelete(Model $row): void
    {
        foreach ($row->files as $file) {
            Storage::disk('local')->delete($file->path);
        }
    }
}
