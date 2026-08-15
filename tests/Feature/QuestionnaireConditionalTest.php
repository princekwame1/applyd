<?php

namespace Tests\Feature;

use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;
use App\Models\User;
use Database\Seeders\DigitalSkillsQuestionnaireSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Only ask this when …". The server decides visibility all over again on
 * submit, so the rules hold whether or not the browser ran any JavaScript.
 */
class QuestionnaireConditionalTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    /** A form asking employment status, then a job title only of the employed. */
    protected function form(): Questionnaire
    {
        $form = Questionnaire::create(['title' => 'Intake', 'is_published' => true]);

        $form->questions()->create([
            'key' => 'employment_status', 'type' => 'radio', 'label' => 'Are you working?',
            'options' => ['Employed', 'Student', 'Looking for work'],
            'is_required' => true, 'is_active' => true, 'sort_order' => 1,
        ]);

        $form->questions()->create([
            'key' => 'job_title', 'type' => 'short_text', 'label' => 'Your job title',
            'is_required' => true, 'is_active' => true, 'sort_order' => 2,
            'visible_when' => ['key' => 'employment_status', 'operator' => 'in', 'values' => ['Employed']],
        ]);

        return $form;
    }

    public function test_a_conditional_question_is_not_required_when_its_rule_is_unmet(): void
    {
        $form = $this->form();

        $this->post(route('forms.store', $form), ['answers' => ['employment_status' => 'Student']])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('forms.thanks', $form));

        $this->assertSame(['employment_status' => 'Student'], QuestionnaireResponse::sole()->answers);
    }

    public function test_a_conditional_question_is_required_once_its_rule_is_met(): void
    {
        $form = $this->form();

        $this->post(route('forms.store', $form), ['answers' => ['employment_status' => 'Employed']])
            ->assertSessionHasErrors('answers.job_title');

        $this->assertSame(0, QuestionnaireResponse::count());

        $this->post(route('forms.store', $form), [
            'answers' => ['employment_status' => 'Employed', 'job_title' => 'Operations Manager'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('Operations Manager', QuestionnaireResponse::sole()->answer('job_title'));
    }

    public function test_an_answer_to_a_question_that_was_never_asked_is_thrown_away(): void
    {
        $form = $this->form();

        // Whatever the browser did or didn't hide, the server asked a student
        // nothing about a job title — so it keeps nothing.
        $this->post(route('forms.store', $form), [
            'answers' => ['employment_status' => 'Student', 'job_title' => 'Smuggled in'],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['employment_status' => 'Student'], QuestionnaireResponse::sole()->answers);
    }

    public function test_a_chain_collapses_when_the_question_at_the_top_is_hidden(): void
    {
        $form = $this->form();

        // Hangs off job_title, which itself only appears for the employed.
        $form->questions()->create([
            'key' => 'team_size', 'type' => 'number', 'label' => 'How many people report to you?',
            'is_required' => true, 'is_active' => true, 'sort_order' => 3,
            'visible_when' => ['key' => 'job_title', 'operator' => 'answered', 'values' => []],
        ]);

        $this->post(route('forms.store', $form), [
            'answers' => ['employment_status' => 'Student', 'job_title' => 'Smuggled in', 'team_size' => 4],
        ])->assertSessionHasNoErrors();

        $this->assertSame(['employment_status' => 'Student'], QuestionnaireResponse::sole()->answers);
    }

    public function test_the_not_in_operator_asks_everyone_except_the_listed_answers(): void
    {
        $form = Questionnaire::create(['title' => 'Intake', 'is_published' => true]);

        $form->questions()->create([
            'key' => 'uses_tools', 'type' => 'checkbox', 'label' => 'Which tools do you use?',
            'options' => ['Sheets', 'Docs', 'None of these'],
            'is_required' => true, 'is_active' => true, 'sort_order' => 1,
        ]);

        $form->questions()->create([
            'key' => 'confidence', 'type' => 'radio', 'label' => 'How confident are you with them?',
            'options' => ['Beginner', 'Advanced'],
            'is_required' => true, 'is_active' => true, 'sort_order' => 2,
            'visible_when' => ['key' => 'uses_tools', 'operator' => 'not_in', 'values' => ['None of these']],
        ]);

        // Ticked "None of these" — the follow-up doesn't apply.
        $this->post(route('forms.store', $form), ['answers' => ['uses_tools' => ['None of these']]])
            ->assertSessionHasNoErrors();

        $this->assertSame(['uses_tools' => ['None of these']], QuestionnaireResponse::sole()->answers);

        // Ticked a real tool — now it's asked, and required.
        $this->post(route('forms.store', $form), ['answers' => ['uses_tools' => ['Sheets']]])
            ->assertSessionHasErrors('answers.confidence');
    }

    public function test_the_public_page_carries_the_rule_but_still_renders_every_question(): void
    {
        $form = $this->form();

        // Without JS every question is on the page — the server, not the
        // browser, is what decides which ones counted.
        $this->get(route('forms.show', $form))
            ->assertOk()
            ->assertSee('Your job title')
            ->assertSee('data-visible-when', false)
            ->assertSee('&quot;key&quot;:&quot;employment_status&quot;', false);
    }

    public function test_an_admin_can_only_point_a_rule_at_an_earlier_question(): void
    {
        $form = $this->form();
        $first = $form->questions()->where('key', 'employment_status')->sole();

        // The first question can't depend on the one that follows it.
        $this->actingAs($this->admin())
            ->put(route('dashboard.questionnaires.questions.update', $first), [
                'label' => 'Are you working?', 'type' => 'radio', 'options' => "Employed\nStudent",
                'is_required' => '1', 'is_active' => '1',
                'condition_key' => 'job_title', 'condition_operator' => 'answered',
            ])
            ->assertSessionHasErrors('condition_key');

        $this->assertNull($first->refresh()->visible_when);
    }

    public function test_a_rule_cannot_name_an_answer_the_controlling_question_does_not_offer(): void
    {
        $form = $this->form();
        $second = $form->questions()->where('key', 'job_title')->sole();

        $this->actingAs($this->admin())
            ->put(route('dashboard.questionnaires.questions.update', $second), [
                'label' => 'Your job title', 'type' => 'short_text',
                'is_required' => '1', 'is_active' => '1',
                'condition_key' => 'employment_status', 'condition_operator' => 'in',
                'condition_values' => ['Retired'],
            ])
            ->assertSessionHasErrors('condition_values');

        // Left exactly as it was.
        $this->assertSame(['Employed'], $second->refresh()->condition()['values']);
    }

    public function test_clearing_the_controlling_question_makes_it_unconditional_again(): void
    {
        $form = $this->form();
        $second = $form->questions()->where('key', 'job_title')->sole();

        $this->actingAs($this->admin())
            ->put(route('dashboard.questionnaires.questions.update', $second), [
                'label' => 'Your job title', 'type' => 'short_text',
                'is_required' => '1', 'is_active' => '1',
                'condition_key' => '',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($second->refresh()->visible_when);
    }

    public function test_a_duplicated_form_keeps_its_conditions_working(): void
    {
        $form = $this->form();

        $this->actingAs($this->admin())->post(route('dashboard.questionnaires.duplicate', $form));

        $copy = Questionnaire::where('id', '!=', $form->id)->sole();
        $copy->update(['is_published' => true]);

        $this->post(route('forms.store', $copy), ['answers' => ['employment_status' => 'Student']])
            ->assertSessionHasNoErrors();

        $this->post(route('forms.store', $copy), ['answers' => ['employment_status' => 'Employed']])
            ->assertSessionHasErrors('answers.job_title');
    }

    public function test_the_seeded_digital_skills_form_asks_its_follow_ups_at_the_right_time(): void
    {
        $this->seed(DigitalSkillsQuestionnaireSeeder::class);

        $form = Questionnaire::where('slug', DigitalSkillsQuestionnaireSeeder::SLUG)->sole();

        $this->assertCount(11, $form->questions);
        // Seeded as a draft so the wording can be checked before it goes out.
        $this->assertFalse($form->is_published);

        $form->update(['is_published' => true]);

        $base = [
            'education_level' => "Bachelor's",
            'field_of_study' => 'Accounting',
            'occupation' => 'Bookkeeper',
            'tools_used' => ['Google Sheets'],
            'digital_skill_level' => 'Intermediate',
            'intended_use' => 'Work',
            'desired_skills' => 'Build a form that feeds a spreadsheet.',
        ];

        // A student who leads nobody: neither follow-up is asked.
        $this->post(route('forms.store', $form), ['answers' => $base + [
            'employment_status' => 'Student',
            'leadership_role' => 'No',
        ]])->assertSessionHasNoErrors();

        $answers = QuestionnaireResponse::latest('id')->sole()->answers;
        $this->assertArrayNotHasKey('job_title', $answers);
        $this->assertArrayNotHasKey('leadership_detail', $answers);

        // Employed and leading a team: both follow-ups become required.
        $this->post(route('forms.store', $form), ['answers' => $base + [
            'employment_status' => 'Employed',
            'leadership_role' => 'Yes',
        ]])->assertSessionHasErrors(['answers.job_title', 'answers.leadership_detail']);
    }

    public function test_the_seeder_can_be_run_twice_without_duplicating_anything(): void
    {
        $this->seed(DigitalSkillsQuestionnaireSeeder::class);
        $this->seed(DigitalSkillsQuestionnaireSeeder::class);

        $this->assertSame(1, Questionnaire::where('slug', DigitalSkillsQuestionnaireSeeder::SLUG)->count());
        $this->assertSame(11, QuestionnaireQuestion::count());
    }
}
