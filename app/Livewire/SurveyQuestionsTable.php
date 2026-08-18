<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class SurveyQuestionsTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = SurveyQuestion::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('survey_id', 'asc');
        $this->setPerPageAccepted([25, 50]);
        $this->setPerPage(25);
    }

    public function builder(): Builder
    {
        return SurveyQuestion::query()->with('survey');
    }

    public function filters(): array
    {
        $options = ['' => 'All'];

        foreach (Survey::ordered()->get() as $survey) {
            $options[$survey->id] = $survey->name;
        }

        return [
            SelectFilter::make('Survey')
                ->options($options)
                ->filter(fn (Builder $builder, string $value) => $builder->where('survey_id', $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Survey', 'survey_id')
                ->sortable()
                ->format(fn ($value, $row) => e($row->survey?->name ?? '—')),
            Column::make('Order', 'sort_order')
                ->sortable(),
            Column::make('Question', 'prompt')
                ->sortable()
                ->searchable(),
            Column::make('Key', 'key')
                ->searchable()
                ->format(fn ($value) => '<code style="font-size:.78rem;">'.e($value).'</code>')
                ->html(),
            Column::make('Type', 'type')
                ->sortable(),
            Column::make('Options', 'options')
                ->format(function ($value, $row) {
                    $options = $row->optionList();

                    return $options
                        ? e(implode(' · ', array_slice($options, 0, 3))).(count($options) > 3
                            ? ' <span style="color:var(--ink-soft);">+'.(count($options) - 3).'</span>'
                            : '')
                        : '<span style="color:var(--ink-soft);">—</span>';
                })
                ->html(),
            Column::make('Required', 'required')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Yes</span>'
                    : '<span class="badge badge-no">Optional</span>')
                ->html(),
            Column::make('Live', 'active')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Live</span>'
                    : '<span class="badge badge-no">Hidden</span>')
                ->html(),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.surveys.partials.question-actions', ['id' => $value]))
                ->html(),
        ];
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
        return Str::limit($row->prompt, 40);
    }

    protected function deleteWarning(): string
    {
        return 'Answers already collected for it are kept — they simply stop being shown.';
    }
}
