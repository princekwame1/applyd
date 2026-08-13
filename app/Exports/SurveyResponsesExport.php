<?php

namespace App\Exports;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SurveyResponsesExport implements FromCollection, WithHeadings, WithMapping
{
    /** @var Collection<int, SurveyQuestion> */
    protected Collection $questions;

    public function __construct(protected Survey $survey)
    {
        // Resolved once so the headings and every mapped row stay in step even
        // if a question is edited mid-export.
        $this->questions = $survey->liveQuestions();
    }

    public function collection(): Collection
    {
        return SurveyResponse::forSurvey($this->survey)->latest()->get();
    }

    public function headings(): array
    {
        return array_merge(
            ['ID', 'Survey', 'Submitted'],
            $this->questions->pluck('prompt')->all(),
        );
    }

    public function map($response): array
    {
        $answers = $this->questions
            ->map(fn ($question) => ($value = $response->answer($question->key)) === null
                ? ''
                : $question->labelFor($value))
            ->all();

        return array_merge(
            [
                $response->id,
                $this->survey->name,
                $response->created_at->format('Y-m-d H:i'),
            ],
            $answers,
        );
    }
}
