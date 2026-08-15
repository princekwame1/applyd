<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD for the forms themselves. Questions hang off a questionnaire and so do
 * the submissions, which is why a form that has collected anything can't
 * simply be deleted — it gets unpublished instead.
 */
class QuestionnaireController extends Controller
{
    public function index()
    {
        return view('dashboard.questionnaires.index');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (Questionnaire::max('sort_order') ?? 0) + 1;

        $questionnaire = Questionnaire::create($data);

        return $this->modalOk(
            $request,
            'dashboard.questionnaires',
            'Form "'.$questionnaire->title.'" created. Add its questions next.',
        );
    }

    /** The question builder for one form: share panel, questions, responses. */
    public function build(Questionnaire $questionnaire)
    {
        return view('dashboard.questionnaires.build', [
            'questionnaire' => $questionnaire,
            'liveCount' => $questionnaire->questions()->active()->count(),
            'totalCount' => $questionnaire->questions()->count(),
            'responseCount' => $questionnaire->responses()->count(),
        ]);
    }

    public function edit(Request $request, Questionnaire $questionnaire)
    {
        if ($request->ajax()) {
            return view('dashboard.questionnaires.partials.questionnaire-form', ['model' => $questionnaire]);
        }

        return redirect()->route('dashboard.questionnaires');
    }

    public function update(Request $request, Questionnaire $questionnaire)
    {
        $questionnaire->update($this->validated($request, $questionnaire));

        return $this->modalOk($request, 'dashboard.questionnaires', 'Form updated.');
    }

    /**
     * Copy the form and its questions, never its responses — the way to re-run
     * the same questionnaire while the previous round keeps its own answers.
     */
    public function duplicate(Questionnaire $questionnaire)
    {
        $copy = $questionnaire->replicate(['slug', 'sort_order']);
        $copy->title = $questionnaire->title.' (copy)';
        $copy->slug = Questionnaire::uniqueSlug($questionnaire->slug.'-copy');
        $copy->sort_order = (Questionnaire::max('sort_order') ?? 0) + 1;
        // Off by default: the copy gets its own link, so it goes live when the
        // admin says so, not the moment it lands.
        $copy->is_published = false;
        $copy->save();

        foreach ($questionnaire->questions()->ordered()->get() as $question) {
            $clone = $question->replicate(['questionnaire_id']);
            $clone->questionnaire_id = $copy->id;
            $clone->save();
        }

        return redirect()
            ->route('dashboard.questionnaires.build', $copy)
            ->with('status', 'Copied to "'.$copy->title.'" with '.$copy->questions()->count().' question(s). It starts unpublished — switch it on when you\'re ready.');
    }

    public function destroy(Questionnaire $questionnaire)
    {
        // Submissions outlive the form they were collected with. Unpublishing
        // closes the link and keeps every response on the responses page.
        if ($questionnaire->responses()->exists()) {
            return redirect()
                ->route('dashboard.questionnaires')
                ->with('error', 'This form has '.$questionnaire->responses()->count().' response(s), so it can\'t be deleted. Unpublish it instead — the link closes and the responses stay.');
        }

        // Done here rather than left to the FK cascade so the behaviour is the
        // same whichever database is underneath.
        $questionnaire->questions()->delete();
        $questionnaire->delete();

        return redirect()
            ->route('dashboard.questionnaires')
            ->with('status', 'Form deleted.');
    }

    private function validated(Request $request, ?Questionnaire $questionnaire = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => [
                'nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(Questionnaire::RESERVED_SLUGS),
                Rule::unique('questionnaires', 'slug')->ignore($questionnaire?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'success_message' => ['nullable', 'string', 'max:500'],
            'submit_label' => ['nullable', 'string', 'max:60'],
            'opens_at' => ['nullable', 'date'],
            // `after:opens_at` only when there is an opening date to compare
            // against — the rule reads a blank field as an unparseable date and
            // would reject every closing date on a form with no opening one.
            'closes_at' => array_values(array_filter([
                'nullable', 'date', $request->filled('opens_at') ? 'after:opens_at' : null,
            ])),
            'response_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'is_published' => ['boolean'],
        ], [
            'slug.regex' => 'The link must be lower-case letters, numbers and dashes, e.g. mentor-application.',
            'slug.not_in' => 'That link is reserved — pick another one.',
            'closes_at.after' => 'The closing date has to come after the opening date.',
        ]);

        // Blank means "name it for me"; the model does that on save.
        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
