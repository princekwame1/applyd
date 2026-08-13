<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD for the check-in forms themselves — one per session, per cohort, per
 * topic. Questions hang off a survey; so do the answers, which is why a survey
 * that has collected anything can't simply be deleted.
 */
class SurveyManagerController extends Controller
{
    public function index()
    {
        return view('dashboard.surveys.manage');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (Survey::max('sort_order') ?? 0) + 1;

        $survey = Survey::create($data);

        return $this->modalOk($request, 'dashboard.surveys.manage', 'Survey "'.$survey->name.'" created. Add its questions next.');
    }

    public function edit(Request $request, Survey $survey)
    {
        if ($request->ajax()) {
            return view('dashboard.surveys.partials.survey-form', ['model' => $survey]);
        }

        return redirect()->route('dashboard.surveys.manage');
    }

    public function update(Request $request, Survey $survey)
    {
        $survey->update($this->validated($request, $survey));

        return $this->modalOk($request, 'dashboard.surveys.manage', 'Survey updated.');
    }

    /**
     * Copy the form, not the answers — the usual way to run the same check-in
     * for the next session while the previous one keeps its own results.
     */
    public function duplicate(Survey $survey)
    {
        $copy = $survey->replicate(['slug', 'sort_order']);
        $copy->name = $survey->name.' (copy)';
        $copy->slug = Survey::uniqueSlug($survey->slug.'-copy');
        $copy->sort_order = (Survey::max('sort_order') ?? 0) + 1;
        // Off by default: it gets its own QR, so it goes live when the admin
        // says so, not the moment the copy lands.
        $copy->is_active = false;
        $copy->save();

        foreach ($survey->questions()->ordered()->get() as $question) {
            $clone = $question->replicate(['survey_id']);
            $clone->survey_id = $copy->id;
            $clone->save();
        }

        return redirect()
            ->route('dashboard.surveys.manage')
            ->with('status', 'Copied to "'.$copy->name.'" with '.$copy->questions()->count().' question(s). It starts hidden — switch it on when you\'re ready.');
    }

    public function destroy(Survey $survey)
    {
        // Answers outlive the form they were collected with. Hiding a survey
        // closes it to the public and keeps every response on the results page.
        if ($survey->responses()->exists()) {
            return redirect()
                ->route('dashboard.surveys.manage')
                ->with('error', 'This survey has '.$survey->responses()->count().' response(s), so it can\'t be deleted. Switch it off instead — the form closes and the results stay.');
        }

        // Done here rather than left to the FK's ON DELETE CASCADE: SQLite
        // ignores foreign keys added by a later ALTER TABLE, so relying on the
        // database would behave differently under MySQL and under the tests.
        $survey->questions()->delete();
        $survey->delete();

        return redirect()
            ->route('dashboard.surveys.manage')
            ->with('status', 'Survey deleted.');
    }

    private function validated(Request $request, ?Survey $survey = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable', 'string', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(Survey::RESERVED_SLUGS),
                Rule::unique('surveys', 'slug')->ignore($survey?->id),
            ],
            'eyebrow' => ['nullable', 'string', 'max:60'],
            'blurb' => ['nullable', 'string', 'max:255'],
            'thanks' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ], [
            'slug.regex' => 'The link must be lower-case letters, numbers and dashes, e.g. week-3-check-in.',
            'slug.not_in' => 'That link is reserved — pick another one.',
        ]);

        // Blank means "name it for me"; the model does that on save.
        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
