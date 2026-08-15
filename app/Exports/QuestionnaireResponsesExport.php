<?php

namespace App\Exports;

use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QuestionnaireResponsesExport implements FromCollection, WithHeadings, WithMapping
{
    /** @var Collection<int, QuestionnaireQuestion> */
    protected Collection $questions;

    public function __construct(protected Questionnaire $questionnaire)
    {
        // Every question, not just the live ones: a hidden question's answers
        // are still in the data, and dropping the column would lose them.
        // Resolved once so the headings and every mapped row stay in step.
        $this->questions = $questionnaire->questions()->ordered()->get();
    }

    public function collection(): Collection
    {
        return QuestionnaireResponse::forQuestionnaire($this->questionnaire)->latest()->get();
    }

    public function headings(): array
    {
        return array_merge(
            ['Reference', 'Form', 'Submitted'],
            $this->questions->pluck('label')->all(),
        );
    }

    public function map($response): array
    {
        $answers = $this->questions
            ->map(fn (QuestionnaireQuestion $question) => $question->formatAnswer($response->answer($question->key)))
            ->all();

        return array_merge(
            [
                $response->reference,
                $this->questionnaire->title,
                $response->created_at->format('Y-m-d H:i'),
            ],
            $answers,
        );
    }
}
