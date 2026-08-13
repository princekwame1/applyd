<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Livewire\SurveysTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Creating and running several check-in forms side by side. The two seeded
 * surveys ("pre"/"post") are the starting point every test builds on.
 */
class SurveyManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function survey(string $slug): Survey
    {
        return Survey::where('slug', $slug)->firstOrFail();
    }

    protected function answer(string $slug, array $answers = ['motivation' => 'Career growth']): SurveyResponse
    {
        return SurveyResponse::create([
            'survey_id' => $this->survey($slug)->id,
            'answers' => $answers,
        ]);
    }

    public function test_the_seeded_surveys_survive_the_migration_with_their_questions(): void
    {
        $this->assertSame(['pre', 'post'], Survey::ordered()->pluck('slug')->all());
        $this->assertCount(4, $this->survey('pre')->questions);
        $this->assertCount(3, $this->survey('post')->questions);
    }

    public function test_a_guest_cannot_reach_the_manage_screen(): void
    {
        $this->get(route('dashboard.surveys.manage'))->assertRedirect(route('login'));
        $this->post(route('dashboard.surveys.manage'), ['name' => 'Sneaky'])->assertRedirect(route('login'));

        $this->assertSame(2, Survey::count());
    }

    public function test_an_admin_can_create_a_survey_and_the_link_is_built_from_the_name(): void
    {
        $this->actingAs($this->admin())
            ->post(route('dashboard.surveys.manage'), [
                'name' => 'Week 3 · Mid-point check',
                'blurb' => 'How are we doing so far?',
                'is_active' => '1',
            ])
            ->assertRedirect(route('dashboard.surveys.manage'));

        $survey = Survey::where('name', 'Week 3 · Mid-point check')->firstOrFail();

        $this->assertSame('week-3-mid-point-check', $survey->slug);
        $this->assertTrue($survey->is_active);
    }

    public function test_a_chosen_link_is_kept_and_must_be_unique(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('dashboard.surveys.manage'), ['name' => 'Day one', 'slug' => 'day-one'])
            ->assertSessionHasNoErrors();

        $this->assertNotNull(Survey::where('slug', 'day-one')->first());

        $this->post(route('dashboard.surveys.manage'), ['name' => 'Day one again', 'slug' => 'day-one'])
            ->assertSessionHasErrors('slug');

        $this->assertSame(3, Survey::count());
    }

    public function test_a_link_that_would_collide_with_the_thanks_page_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('dashboard.surveys.manage'), ['name' => 'Thanks', 'slug' => 'thanks'])
            ->assertSessionHasErrors('slug');
    }

    public function test_a_name_that_slugs_to_a_reserved_word_still_gets_a_usable_link(): void
    {
        $this->actingAs($this->admin())
            ->post(route('dashboard.surveys.manage'), ['name' => 'Thanks'])
            ->assertSessionHasNoErrors();

        // Auto-generated, so it sidesteps the reserved word rather than failing.
        $this->assertSame('thanks-survey', Survey::where('name', 'Thanks')->first()->slug);
    }

    public function test_a_new_survey_runs_alongside_the_existing_ones_without_touching_their_data(): void
    {
        $this->actingAs($this->admin());

        $this->answer('pre');
        $this->answer('post', ['expectations' => 5]);

        $this->post(route('dashboard.surveys.manage'), ['name' => 'Week 3 check', 'slug' => 'week-3', 'is_active' => '1']);

        $survey = $this->survey('week-3');

        $this->post(route('dashboard.surveys.questions.store'), [
            'survey_id' => $survey->id,
            'key' => 'pace',
            'type' => 'scale',
            'prompt' => 'How is the pace?',
            'options' => "Too slow\nAbout right\nToo fast",
            'required' => '1',
            'active' => '1',
        ])->assertSessionHasNoErrors();

        // The new form works on its own URL…
        $this->post(route('surveys.store', 'week-3'), ['answers' => ['pace' => 2]])
            ->assertRedirect(route('surveys.thanks'));

        // …and every survey keeps exactly its own answers.
        $this->assertSame(1, SurveyResponse::forSurvey('pre')->count());
        $this->assertSame(1, SurveyResponse::forSurvey('post')->count());
        $this->assertSame(2, SurveyResponse::forSurvey('week-3')->first()->answer('pace'));
    }

    public function test_duplicating_a_survey_copies_its_questions_but_not_its_answers(): void
    {
        $this->actingAs($this->admin());

        $this->answer('pre');

        $this->post(route('dashboard.surveys.manage.duplicate', $this->survey('pre')))
            ->assertRedirect(route('dashboard.surveys.manage'));

        $copy = Survey::where('slug', 'pre-copy')->firstOrFail();

        $this->assertSame('Before we start (copy)', $copy->name);
        $this->assertCount(4, $copy->questions);
        $this->assertSame(0, $copy->responses()->count());
        $this->assertSame(1, SurveyResponse::forSurvey('pre')->count());

        // It starts closed so a half-built copy can't collect anything.
        $this->assertFalse($copy->is_active);
        $this->get(route('surveys.show', 'pre-copy'))->assertNotFound();
    }

    public function test_the_copy_keeps_its_own_answers_apart_from_the_original(): void
    {
        $this->actingAs($this->admin());

        $this->answer('pre');
        $this->post(route('dashboard.surveys.manage.duplicate', $this->survey('pre')));

        $copy = Survey::where('slug', 'pre-copy')->firstOrFail();
        $copy->update(['is_active' => true]);

        $this->post(route('surveys.store', 'pre-copy'), ['answers' => [
            'motivation' => 'Plain curiosity',
            'comfort' => 2,
            'source' => 'Instagram',
        ]])->assertSessionHasNoErrors();

        $this->assertSame('Career growth', SurveyResponse::forSurvey('pre')->first()->answer('motivation'));
        $this->assertSame('Plain curiosity', SurveyResponse::forSurvey('pre-copy')->first()->answer('motivation'));
    }

    public function test_a_survey_with_responses_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin());

        $this->answer('pre');

        $this->delete(route('dashboard.surveys.manage.destroy', $this->survey('pre')))
            ->assertRedirect(route('dashboard.surveys.manage'))
            ->assertSessionHas('error');

        // The point of the guard: the form and every answer are still there.
        $this->assertNotNull(Survey::where('slug', 'pre')->first());
        $this->assertSame(1, SurveyResponse::count());
        $this->assertSame(4, SurveyQuestion::forSurvey('pre')->count());
    }

    public function test_a_survey_with_no_responses_can_be_deleted_and_takes_its_questions(): void
    {
        $this->actingAs($this->admin());

        $survey = $this->survey('post');
        $questionIds = $survey->questions->pluck('id');

        $this->delete(route('dashboard.surveys.manage.destroy', $survey))
            ->assertRedirect(route('dashboard.surveys.manage'));

        $this->assertNull(Survey::where('slug', 'post')->first());
        $this->assertSame(0, SurveyQuestion::whereIn('id', $questionIds)->count());
    }

    public function test_closing_a_survey_keeps_its_results_on_the_dashboard(): void
    {
        $this->actingAs($this->admin());

        $this->answer('pre');

        $this->put(route('dashboard.surveys.manage.update', $this->survey('pre')), [
            'name' => 'Before we start',
            'slug' => 'pre',
        ])->assertRedirect(route('dashboard.surveys.manage'));

        $this->assertFalse($this->survey('pre')->is_active);

        // Closed to the public…
        $this->get(route('surveys.show', 'pre'))->assertNotFound();

        // …still readable to the admin.
        $this->get(route('dashboard.surveys', ['survey' => 'pre']))
            ->assertOk()
            ->assertSee('Career growth');
    }

    public function test_renaming_a_survey_leaves_its_answers_attached(): void
    {
        $this->actingAs($this->admin());

        $this->answer('pre');

        $this->put(route('dashboard.surveys.manage.update', $this->survey('pre')), [
            'name' => 'Arrival check-in',
            'slug' => 'arrival',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        // The answers hang off the survey id, so a new name and a new URL move
        // with them rather than orphaning them.
        $survey = $this->survey('arrival');

        $this->assertSame(1, $survey->responses()->count());
        $this->assertSame('Career growth', $survey->responses()->first()->answer('motivation'));
        $this->get(route('surveys.show', 'arrival'))->assertOk();
    }

    public function test_the_manage_screen_lists_every_survey_with_its_counts(): void
    {
        $this->actingAs($this->admin());

        $this->answer('pre');
        $this->answer('pre');

        $this->get(route('dashboard.surveys.manage'))->assertOk();

        Livewire::test(SurveysTable::class)
            ->assertSee('Before we start')
            ->assertSee('Before you go')
            ->assertSee('/check-in/pre')
            ->assertSee('4 of 4 live');     // questions on the pre survey
    }
}
