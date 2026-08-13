<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SurveyQuestionsController extends Controller
{
    public function index()
    {
        return view('dashboard.surveys.questions');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['sort_order'] = $data['sort_order']
            ?? (SurveyQuestion::where('survey_id', $data['survey_id'])->max('sort_order') ?? 0) + 1;

        SurveyQuestion::create($data);

        return $this->modalOk($request, 'dashboard.surveys.questions', 'Question added.');
    }

    public function edit(Request $request, SurveyQuestion $question)
    {
        if ($request->ajax()) {
            return view('dashboard.surveys.partials.question-form', ['model' => $question]);
        }

        return redirect()->route('dashboard.surveys.questions');
    }

    public function update(Request $request, SurveyQuestion $question)
    {
        $question->update($this->validated($request, $question));

        return $this->modalOk($request, 'dashboard.surveys.questions', 'Question updated.');
    }

    public function destroy(SurveyQuestion $question)
    {
        $question->delete();

        return redirect()
            ->route('dashboard.surveys.questions')
            ->with('status', 'Question deleted. Answers already collected for it are kept.');
    }

    private function validated(Request $request, ?SurveyQuestion $question = null): array
    {
        $rules = [
            'type' => ['required', Rule::in(SurveyQuestion::TYPES)],
            'prompt' => ['required', 'string', 'max:255'],
            'options' => ['nullable', 'string', 'max:2000'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'required' => ['boolean'],
            'active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];

        // Answers are stored keyed by `key`, so letting it change on an existing
        // question would silently orphan every answer already collected. It's
        // set once, at creation.
        if (! $question) {
            $rules['survey_id'] = ['required', Rule::exists('surveys', 'id')];
            $rules['key'] = [
                'required', 'string', 'max:60', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('survey_questions', 'key')->where('survey_id', $request->input('survey_id')),
            ];
        }

        $data = $request->validate($rules, [
            'key.regex' => 'The key must be lower-case letters, numbers and underscores, starting with a letter.',
        ]);

        $data['required'] = $request->boolean('required');
        $data['active'] = $request->boolean('active');
        $data['options'] = $this->parseOptions($data['type'], $data['options'] ?? null);

        if ($data['type'] !== 'text') {
            $data['placeholder'] = null;

            if (count($data['options']) < 2) {
                throw ValidationException::withMessages([
                    'options' => 'Give this question at least two options, one per line.',
                ]);
            }
        }

        return $data;
    }

    /** One option per line; free-text questions have none. */
    private function parseOptions(string $type, ?string $raw): ?array
    {
        if ($type === 'text') {
            return null;
        }

        return collect(preg_split('/\r\n|\r|\n/', (string) $raw))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
