<?php

namespace App\Http\Controllers;

use App\Models\JobOpening;
use App\Models\JobScreeningCriterion as Criterion;
use App\Support\FitScore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The rubric behind a job: what the role asks for and what each thing is
 * worth. Every applicant is scored against it the moment they apply.
 *
 * Setting it is **free**, like posting the job itself — what a plan buys is
 * reading the people who applied, and a paywall in front of the criteria
 * would only mean worse-scored applicants for everyone.
 *
 * Editing anything re-scores the whole list. A screen showing two candidates
 * measured against different rubrics is worse than no score at all.
 */
class CompanyScreeningController extends Controller
{
    public function index(Request $request, JobOpening $opening)
    {
        $this->authorizeOpening($request, $opening);

        return view('company.screening', [
            'opening' => $opening,
            'criteria' => $opening->screeningCriteria()->get(),
            'applicants' => $opening->applications()->count(),
        ]);
    }

    public function store(Request $request, JobOpening $opening)
    {
        $this->authorizeOpening($request, $opening);

        $data = $this->validated($request);

        $criterion = new Criterion($data + [
            'sort_order' => (int) $opening->screeningCriteria()->max('sort_order') + 1,
        ]);
        $criterion->job_opening_id = $opening->id;

        // Set once, here, and never again: it is the key the answers are
        // filed under, so a later rename must not move it.
        if ($criterion->kind === Criterion::QUESTION) {
            $criterion->key = Criterion::uniqueKey($opening->id, $data['label']);
        }

        $criterion->save();

        return $this->done($opening, 'Criterion added.');
    }

    /**
     * Wording, weight and what counts as a pass can all change. The kind, the
     * answer type and the key cannot — those are what the collected answers
     * are shaped by, and moving them would silently re-interpret every
     * application already in the list.
     */
    public function update(Request $request, JobOpening $opening, Criterion $criterion)
    {
        $this->authorizeOpening($request, $opening);
        $this->authorizeCriterion($opening, $criterion);

        $data = $this->validated($request, $criterion);

        $criterion->update([
            'label' => $data['label'],
            'weight' => $data['weight'],
            'is_knockout' => $data['is_knockout'],
            'options' => $data['options'] ?? $criterion->options,
            'expected' => $data['expected'] ?? $criterion->expected,
        ]);

        return $this->done($opening, 'Criterion updated.');
    }

    public function destroy(Request $request, JobOpening $opening, Criterion $criterion)
    {
        $this->authorizeOpening($request, $opening);
        $this->authorizeCriterion($opening, $criterion);

        // Answers already collected under this key stay in the row. They are
        // what the candidate actually said, and deleting the question is not
        // grounds for rewriting that.
        $criterion->delete();

        return $this->done($opening, 'Criterion removed.');
    }

    private function validated(Request $request, ?Criterion $criterion = null): array
    {
        $kind = $criterion?->kind ?? $request->input('kind');
        $answerType = $criterion?->answer_type ?? $request->input('answer_type');

        $rules = [
            'label' => ['required', 'string', 'max:160'],
            'weight' => ['required', 'integer', 'min:1', 'max:5'],
            'is_knockout' => ['nullable', 'boolean'],
        ];

        if (! $criterion) {
            $rules['kind'] = ['required', Rule::in(Criterion::KINDS)];
            $rules['answer_type'] = [
                Rule::requiredIf($kind === Criterion::QUESTION),
                Rule::in(Criterion::ANSWER_TYPES),
            ];
        }

        if ($kind === Criterion::QUESTION && $answerType === Criterion::NUMBER) {
            $rules['minimum'] = ['required', 'numeric', 'min:0', 'max:99'];
        }

        if ($kind === Criterion::QUESTION && $answerType === Criterion::CHOICE) {
            $rules['choices'] = ['required', 'string', 'max:1000'];
            $rules['accepted'] = ['required', 'string', 'max:1000'];
        }

        $data = $request->validate($rules);

        $out = [
            'label' => $data['label'],
            'weight' => (int) $data['weight'],
            'is_knockout' => $request->boolean('is_knockout'),
        ];

        if (! $criterion) {
            $out['kind'] = $data['kind'];
            $out['answer_type'] = $data['kind'] === Criterion::QUESTION ? $data['answer_type'] : null;
        }

        if ($kind === Criterion::QUESTION && $answerType === Criterion::NUMBER) {
            $out['expected'] = [(float) $data['minimum']];
        }

        if ($kind === Criterion::QUESTION && $answerType === Criterion::YES_NO) {
            $out['expected'] = ['yes'];
        }

        if ($kind === Criterion::QUESTION && $answerType === Criterion::CHOICE) {
            $out['options'] = static::lines($data['choices']);

            // Only ever the options themselves: a pass mark pointing at an
            // answer nobody can give could never be met.
            $out['expected'] = array_values(array_intersect(
                static::lines($data['accepted']),
                $out['options'],
            ));

            if ($out['expected'] === []) {
                throw ValidationException::withMessages([
                    'accepted' => 'Each accepted answer has to be one of the options above, spelled the same way.',
                ]);
            }
        }

        return $out;
    }

    /** @return array<int, string> */
    private static function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function done(JobOpening $opening, string $message): RedirectResponse
    {
        $rescored = FitScore::rescore($opening);

        return redirect()
            ->route('company.screening', $opening)
            ->with('status', $rescored > 0
                ? $message.' '.$rescored.' '.Str::plural('applicant', $rescored).' re-scored against the new criteria.'
                : $message);
    }

    private function authorizeOpening(Request $request, JobOpening $opening): void
    {
        abort_unless($opening->company_id === $request->user()->company?->id, 403);
    }

    private function authorizeCriterion(JobOpening $opening, Criterion $criterion): void
    {
        abort_unless($criterion->job_opening_id === $opening->id, 404);
    }
}
