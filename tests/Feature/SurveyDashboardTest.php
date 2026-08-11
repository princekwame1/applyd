<?php

namespace Tests\Feature;

use App\Livewire\SurveyResponsesTable;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Support\Surveys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SurveyDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function response(array $answers, string $type = 'pre'): SurveyResponse
    {
        return SurveyResponse::create(['survey_type' => $type, 'answers' => $answers]);
    }

    public function test_a_guest_cannot_reach_the_results(): void
    {
        $this->get(route('dashboard.surveys'))->assertRedirect(route('login'));
    }

    public function test_the_results_page_shows_counts_per_option(): void
    {
        $this->actingAs($this->admin());

        $this->response(['motivation' => 'Career growth', 'comfort' => 4]);
        $this->response(['motivation' => 'Career growth', 'comfort' => 2]);
        $this->response(['motivation' => 'Plain curiosity', 'comfort' => 4]);

        $this->get(route('dashboard.surveys'))
            ->assertOk()
            ->assertSee('What made you sign up for this bootcamp?')
            ->assertSee('2 · 67%')      // Career growth
            ->assertSee('1 · 33%');     // Plain curiosity
    }

    public function test_a_scale_question_reports_its_average_and_labels(): void
    {
        $this->actingAs($this->admin());

        $this->response(['comfort' => 2]);
        $this->response(['comfort' => 4]);

        $this->get(route('dashboard.surveys'))
            ->assertOk()
            ->assertSee('average 3 of 4')
            ->assertSee('2 · Some experience');
    }

    public function test_free_text_answers_are_listed_verbatim(): void
    {
        $this->actingAs($this->admin());

        $this->response(['goal' => 'Finally understand spreadsheets']);

        $this->get(route('dashboard.surveys'))
            ->assertOk()
            ->assertSee('Finally understand spreadsheets');
    }

    public function test_the_two_surveys_are_counted_separately(): void
    {
        $this->actingAs($this->admin());

        $this->response(['motivation' => 'Career growth'], 'pre');
        $this->response(['expectations' => 5], 'post');
        $this->response(['expectations' => 4], 'post');

        $this->get(route('dashboard.surveys', ['survey' => 'post']))
            ->assertOk()
            ->assertSee("How well did today's session meet your expectations?")
            ->assertDontSee('What made you sign up for this bootcamp?');
    }

    public function test_an_unknown_tab_falls_back_to_the_first_survey(): void
    {
        $this->actingAs($this->admin());

        $this->response(['motivation' => 'Career growth']);

        $this->get(route('dashboard.surveys', ['survey' => 'nonsense']))
            ->assertOk()
            ->assertSee('What made you sign up for this bootcamp?');
    }

    public function test_the_responses_table_shows_each_answer(): void
    {
        $this->actingAs($this->admin());

        $this->response(['motivation' => 'Career growth', 'comfort' => 3]);

        Livewire::test(SurveyResponsesTable::class, ['surveyType' => 'pre'])
            ->assertSee('Career growth')
            ->assertSee('3 · Comfortable');
    }

    public function test_a_skipped_answer_shows_as_a_dash_rather_than_blank(): void
    {
        $this->actingAs($this->admin());

        $this->response(['motivation' => 'Career growth']);

        Livewire::test(SurveyResponsesTable::class, ['surveyType' => 'pre'])
            ->assertSee('—');
    }

    public function test_a_response_can_be_deleted(): void
    {
        $this->actingAs($this->admin());

        $response = $this->response(['motivation' => 'Career growth']);

        $this->delete(route('dashboard.surveys.response.destroy', $response))
            ->assertRedirect(route('dashboard.surveys', ['survey' => 'pre']));

        $this->assertSame(0, SurveyResponse::count());
    }

    public function test_the_export_downloads_a_spreadsheet(): void
    {
        $this->actingAs($this->admin());

        $this->response(['motivation' => 'Career growth']);

        $this->get(route('dashboard.surveys.export', ['survey' => 'pre']))
            ->assertOk()
            ->assertDownload('pulse-check-pre-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_a_question_can_be_added_to_a_survey(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('dashboard.surveys.questions.store'), [
            'survey_type' => 'post',
            'key' => 'recommend',
            'type' => 'choice',
            'prompt' => 'Would you recommend this session?',
            'options' => "Yes\nNo\nNot sure",
            'required' => '1',
            'active' => '1',
        ])->assertRedirect(route('dashboard.surveys.questions'));

        $question = SurveyQuestion::forSurvey('post')->where('key', 'recommend')->first();

        $this->assertNotNull($question);
        $this->assertSame(['Yes', 'No', 'Not sure'], $question->optionList());

        // It's live immediately — the public form reads the same table.
        $this->get(route('surveys.show', 'post'))->assertSee('Would you recommend this session?');
    }

    public function test_a_choice_question_needs_at_least_two_options(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('dashboard.surveys.questions.store'), [
            'survey_type' => 'post',
            'key' => 'lonely',
            'type' => 'choice',
            'prompt' => 'One option only?',
            'options' => 'Yes',
        ])->assertSessionHasErrors('options');

        $this->assertNull(SurveyQuestion::where('key', 'lonely')->first());
    }

    public function test_a_duplicate_key_within_one_survey_is_rejected(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('dashboard.surveys.questions.store'), [
            'survey_type' => 'pre',
            'key' => 'motivation',
            'type' => 'text',
            'prompt' => 'Another motivation question',
        ])->assertSessionHasErrors('key');
    }

    public function test_the_same_key_may_be_reused_in_the_other_survey(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('dashboard.surveys.questions.store'), [
            'survey_type' => 'post',
            'key' => 'motivation',
            'type' => 'text',
            'prompt' => 'What kept you motivated today?',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(SurveyQuestion::forSurvey('post')->where('key', 'motivation')->first());
    }

    public function test_editing_a_question_cannot_change_its_key(): void
    {
        $this->actingAs($this->admin());

        $question = SurveyQuestion::forSurvey('pre')->where('key', 'motivation')->first();
        $this->response(['motivation' => 'Career growth']);

        $this->put(route('dashboard.surveys.questions.update', $question), [
            'key' => 'why_they_came',
            'survey_type' => 'post',
            'type' => 'choice',
            'prompt' => 'What brought you here?',
            'options' => "Career growth\nPlain curiosity",
            'required' => '1',
            'active' => '1',
        ])->assertRedirect(route('dashboard.surveys.questions'));

        $question->refresh();

        // The prompt is editable; the key and survey are not — answers are stored
        // under the key, so changing it would orphan everything collected so far.
        $this->assertSame('What brought you here?', $question->prompt);
        $this->assertSame('motivation', $question->key);
        $this->assertSame('pre', $question->survey_type);
        $this->assertSame('Career growth', SurveyResponse::first()->answer('motivation'));
    }

    public function test_hiding_a_question_keeps_its_answers_in_the_database(): void
    {
        $this->actingAs($this->admin());

        $this->response(['motivation' => 'Career growth']);

        SurveyQuestion::forSurvey('pre')->where('key', 'motivation')->update(['active' => false]);

        $this->assertSame('Career growth', SurveyResponse::first()->answer('motivation'));
        $this->assertCount(3, Surveys::questions('pre'));
    }
}
