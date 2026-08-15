<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use App\Support\Questionnaires;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The fields on one form. A question belongs to exactly one questionnaire and
 * stays there — its `key` is what every collected answer is filed under.
 */
class QuestionnaireQuestionController extends Controller
{
    /** The "add a question" form, rendered into the shared modal. */
    public function create(Request $request, Questionnaire $questionnaire)
    {
        if ($request->ajax()) {
            return view('dashboard.questionnaires.partials.question-form', [
                'model' => null,
                'questionnaire' => $questionnaire,
                'controllers' => Questionnaires::controllersFor($questionnaire->questions()->ordered()->get()),
            ]);
        }

        return redirect()->route('dashboard.questionnaires.build', $questionnaire);
    }

    public function store(Request $request, Questionnaire $questionnaire)
    {
        $data = $this->validated($request, $questionnaire);

        $data['questionnaire_id'] = $questionnaire->id;
        $data['sort_order'] = ($questionnaire->questions()->max('sort_order') ?? 0) + 1;

        QuestionnaireQuestion::create($data);

        return $this->modalOk($request, 'dashboard.questionnaires', 'Question added.');
    }

    public function edit(Request $request, QuestionnaireQuestion $question)
    {
        if ($request->ajax()) {
            return view('dashboard.questionnaires.partials.question-form', [
                'model' => $question,
                'questionnaire' => $question->questionnaire,
                'controllers' => Questionnaires::controllersFor(
                    $question->questionnaire->questions()->ordered()->get(),
                    $question,
                ),
            ]);
        }

        return redirect()->route('dashboard.questionnaires.build', $question->questionnaire);
    }

    public function update(Request $request, QuestionnaireQuestion $question)
    {
        $question->update($this->validated($request, $question->questionnaire, $question));

        return $this->modalOk($request, 'dashboard.questionnaires', 'Question updated.');
    }

    public function destroy(QuestionnaireQuestion $question)
    {
        $questionnaire = $question->questionnaire;
        $question->delete();

        return redirect()
            ->route('dashboard.questionnaires.build', $questionnaire)
            ->with('status', 'Question deleted. Answers already collected for it are kept.');
    }

    private function validated(Request $request, Questionnaire $questionnaire, ?QuestionnaireQuestion $question = null): array
    {
        $rules = [
            'type' => ['required', Rule::in(array_keys(QuestionnaireQuestion::TYPES))],
            'label' => ['required', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:255'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'options' => ['nullable', 'string', 'max:4000'],
            'max_select' => ['nullable', 'integer', 'min:1', 'max:50'],
            // A leading dot is how most people write an extension, so accept it
            // here and strip it in normaliseMimes() rather than bouncing them.
            'mimes' => ['nullable', 'string', 'max:255', 'regex:/^\s*\.?[a-z0-9]+(?:\s*,\s*\.?[a-z0-9]+)*\s*$/i'],
            'max_kb' => ['nullable', 'integer', 'min:64', 'max:20480'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'condition_key' => ['nullable', 'string', 'max:60'],
            'condition_operator' => ['nullable', Rule::in(array_keys(QuestionnaireQuestion::OPERATORS))],
            'condition_values' => ['nullable', 'array', 'max:50'],
            'condition_values.*' => ['string', 'max:255'],
        ];

        // Answers are stored keyed by `key`, so letting it change on an
        // existing question would silently orphan everything already collected.
        // It's set once, at creation.
        if (! $question) {
            $rules['key'] = [
                'required', 'string', 'max:60', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('questionnaire_questions', 'key')->where('questionnaire_id', $questionnaire->id),
            ];
        }

        $data = $request->validate($rules, [
            'key.regex' => 'The key must be lower-case letters, numbers and underscores, starting with a letter.',
            'key.unique' => 'This form already has a question with that key.',
            'mimes.regex' => 'List file extensions separated by commas, e.g. pdf, docx, png.',
        ]);

        $type = $data['type'];

        $data['is_required'] = $request->boolean('is_required');
        $data['is_active'] = $request->boolean('is_active');
        $data['options'] = $this->parseOptions($type, $data['options'] ?? null);
        $data['settings'] = $this->settingsFor($type, $data);
        $data['visible_when'] = $this->conditionFor($questionnaire, $question, $data);

        if (in_array($type, QuestionnaireQuestion::OPTION_TYPES, true) && count($data['options']) < 2) {
            throw ValidationException::withMessages([
                'options' => 'Give this question at least two options, one per line.',
            ]);
        }

        // Only the option types and the free-text ones use these; keeping them
        // on a date or a file field would just be dead data on the row.
        if (! in_array($type, ['short_text', 'long_text', 'number', 'email', 'phone'], true)) {
            $data['placeholder'] = null;
        }

        unset(
            $data['max_select'], $data['mimes'], $data['max_kb'],
            $data['condition_key'], $data['condition_operator'], $data['condition_values'],
        );

        return $data;
    }

    /**
     * "Only ask this when …". Blank controller means always ask. The named
     * question has to be a real, earlier one on this same form, and the values
     * have to be options it actually offers — otherwise the rule could never
     * be satisfied and the question would silently never appear.
     */
    private function conditionFor(Questionnaire $questionnaire, ?QuestionnaireQuestion $question, array $data): ?array
    {
        $key = trim((string) ($data['condition_key'] ?? ''));

        if ($key === '') {
            return null;
        }

        $candidates = Questionnaires::controllersFor(
            $questionnaire->questions()->ordered()->get(),
            $question,
        );

        $controller = $candidates->firstWhere('key', $key);

        if (! $controller) {
            throw ValidationException::withMessages([
                'condition_key' => 'Pick a question that comes before this one on the form — a question can only depend on one that has already been answered.',
            ]);
        }

        $operator = $data['condition_operator'] ?? 'in';
        $values = array_values(array_unique(array_filter((array) ($data['condition_values'] ?? []), fn ($v) => $v !== '')));

        if ($operator === 'answered') {
            return ['key' => $key, 'operator' => 'answered', 'values' => []];
        }

        if (! $values) {
            throw ValidationException::withMessages([
                'condition_values' => 'Choose which answers should bring this question up.',
            ]);
        }

        if ($controller->hasOptions() && array_diff($values, $controller->optionList())) {
            throw ValidationException::withMessages([
                'condition_values' => 'Those answers aren\'t on "'.$controller->label.'" any more. Pick from its current options.',
            ]);
        }

        return ['key' => $key, 'operator' => $operator, 'values' => $values];
    }

    /** One option per line; everything that isn't a list question has none. */
    private function parseOptions(string $type, ?string $raw): ?array
    {
        if (! in_array($type, QuestionnaireQuestion::OPTION_TYPES, true)) {
            return null;
        }

        return collect(preg_split('/\r\n|\r|\n/', (string) $raw))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** Per-type extras, stored together so the row only carries what it uses. */
    private function settingsFor(string $type, array $data): ?array
    {
        $settings = match ($type) {
            'checkbox' => ['max_select' => $data['max_select'] ?? null],
            'file' => [
                'mimes' => $this->normaliseMimes($data['mimes'] ?? null),
                'max_kb' => (int) ($data['max_kb'] ?? QuestionnaireQuestion::DEFAULT_FILE_MAX_KB),
            ],
            default => [],
        };

        $settings = array_filter($settings, fn ($v) => $v !== null && $v !== '');

        return $settings ?: null;
    }

    private function normaliseMimes(?string $raw): string
    {
        $list = collect(explode(',', (string) $raw))
            ->map(fn ($ext) => strtolower(trim($ext, " \t\n\r\0\x0B.")))
            ->filter()
            ->unique()
            ->values();

        return $list->isEmpty() ? QuestionnaireQuestion::DEFAULT_FILE_MIMES : $list->implode(',');
    }
}
