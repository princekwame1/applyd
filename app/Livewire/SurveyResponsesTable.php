<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

/**
 * Raw check-in responses, one row per submission. The aggregate view lives
 * above it on the results page — this is the "show me the actual answers" half.
 */
class SurveyResponsesTable extends DataTableComponent
{
    use WithSkeletonLoader;

    protected $model = SurveyResponse::class;

    public int $surveyId = 0;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(10);
        $this->setSearchDisabled();
        $this->setBulkActionsDisabled();

        // The answer columns are label columns, and rappasoft only SELECTs the
        // fields behind real columns — without this the model arrives with no
        // `answers` and every cell renders as "not answered".
        $this->setAdditionalSelects(['survey_responses.answers']);
    }

    public function builder(): Builder
    {
        return SurveyResponse::query()->where('survey_id', $this->surveyId);
    }

    public function columns(): array
    {
        $columns = [
            Column::make('Submitted', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y g:ia')),
        ];

        // One column per live question. They're label columns: the answers sit
        // inside a JSON map, so there is no real DB column to sort or search on.
        foreach ($this->questions() as $question) {
            $columns[] = Column::make(Str::limit($question->prompt, 30))
                ->label(fn ($row) => $this->renderAnswer($question, $row))
                ->html();
        }

        $columns[] = Column::make('Actions', 'id')
            ->format(fn ($value) => view('dashboard.surveys.partials.response-actions', ['id' => $value]))
            ->html();

        return $columns;
    }

    /** The live questions of the survey being viewed, in display order. */
    protected function questions(): Collection
    {
        return SurveyQuestion::where('survey_id', $this->surveyId)->active()->ordered()->get();
    }

    protected function renderAnswer(SurveyQuestion $question, SurveyResponse $response): string
    {
        $value = $response->answer($question->key);

        if ($value === null) {
            return '<span style="color:var(--ink-soft);">—</span>';
        }

        if ($question->type === 'text') {
            return '<span title="'.e($value).'">'.e(Str::limit($value, 60)).'</span>';
        }

        return e($question->labelFor($value));
    }
}
