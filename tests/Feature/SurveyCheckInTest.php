<?php

namespace Tests\Feature;

use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public "Pulse Check" flow at /check-in. Questions come from the
 * survey_questions table (seeded by the migration), so these tests lean on the
 * seeded set and adjust it where the behaviour under test needs something else.
 */
class SurveyCheckInTest extends TestCase
{
    use RefreshDatabase;

    /** A complete, valid set of answers for the pre-session survey. */
    protected function validPreAnswers(): array
    {
        return [
            'motivation' => 'Career growth',
            'goal' => 'Learn Notion properly',
            'comfort' => 3,
            'source' => 'Instagram',
        ];
    }

    public function test_the_picker_lists_every_survey_that_has_questions(): void
    {
        $this->get(route('surveys.index'))
            ->assertOk()
            ->assertSee('Before we start')
            ->assertSee('Before you go');
    }

    public function test_a_survey_with_no_live_questions_is_left_off_the_picker(): void
    {
        SurveyQuestion::forSurvey('post')->update(['active' => false]);

        $this->get(route('surveys.index'))
            ->assertOk()
            ->assertSee('Before we start')
            ->assertDontSee('Before you go');
    }

    public function test_the_survey_page_renders_its_questions(): void
    {
        $this->get(route('surveys.show', 'pre'))
            ->assertOk()
            ->assertSee('What made you sign up for this bootcamp?')
            ->assertSee('Career growth')
            ->assertSee('How comfortable are you with digital tools right now?');
    }

    public function test_an_unknown_survey_type_is_a_404(): void
    {
        $this->get(route('surveys.show', 'midway'))->assertNotFound();
    }

    public function test_a_valid_submission_is_stored_and_redirects_to_thanks(): void
    {
        $this->post(route('surveys.store', 'pre'), ['answers' => $this->validPreAnswers()])
            ->assertRedirect(route('surveys.thanks'));

        $this->assertSame(1, SurveyResponse::count());

        $response = SurveyResponse::first();
        $this->assertSame('pre', $response->survey_type);
        $this->assertSame('Career growth', $response->answer('motivation'));
        $this->assertSame('Learn Notion properly', $response->answer('goal'));
    }

    public function test_a_scale_answer_is_stored_as_a_number(): void
    {
        $this->post(route('surveys.store', 'pre'), ['answers' => $this->validPreAnswers()]);

        // Posted as a string by the form; it has to come back out as an int so
        // the results screen can average it.
        $this->assertSame(3, SurveyResponse::first()->answer('comfort'));
    }

    public function test_a_missing_required_answer_is_rejected_and_stores_nothing(): void
    {
        $answers = $this->validPreAnswers();
        unset($answers['motivation']);

        $this->post(route('surveys.store', 'pre'), ['answers' => $answers])
            ->assertSessionHasErrors('answers.motivation');

        $this->assertSame(0, SurveyResponse::count());
    }

    public function test_the_form_renders_again_with_the_error_and_the_answers_kept(): void
    {
        $answers = $this->validPreAnswers();
        unset($answers['motivation']);

        $this->from(route('surveys.show', 'pre'))
            ->post(route('surveys.store', 'pre'), ['answers' => $answers])
            ->assertRedirect(route('surveys.show', 'pre'));

        // The stepper reopens on the failed question, so the error has to survive
        // the redirect and the already-typed answers have to come back with it.
        $this->followingRedirects()
            ->from(route('surveys.show', 'pre'))
            ->post(route('surveys.store', 'pre'), ['answers' => $answers])
            ->assertOk()
            ->assertSee('Please check your answers below.')
            ->assertSee('Learn Notion properly');
    }

    public function test_a_skipped_optional_question_is_left_out_of_the_stored_answers(): void
    {
        $answers = $this->validPreAnswers();
        $answers['goal'] = '';

        $this->post(route('surveys.store', 'pre'), ['answers' => $answers])
            ->assertSessionHasNoErrors();

        // Absent, not stored as an empty string — otherwise "answered" counts on
        // the results screen would be inflated by people who skipped.
        $this->assertArrayNotHasKey('goal', SurveyResponse::first()->answers);
        $this->assertNull(SurveyResponse::first()->answer('goal'));
    }

    public function test_a_choice_answer_outside_the_option_list_is_rejected(): void
    {
        $answers = $this->validPreAnswers();
        $answers['motivation'] = 'Because I felt like it';

        $this->post(route('surveys.store', 'pre'), ['answers' => $answers])
            ->assertSessionHasErrors('answers.motivation');

        $this->assertSame(0, SurveyResponse::count());
    }

    public function test_a_scale_answer_beyond_the_top_of_the_scale_is_rejected(): void
    {
        $answers = $this->validPreAnswers();
        $answers['comfort'] = 9;   // the seeded scale only goes to 4

        $this->post(route('surveys.store', 'pre'), ['answers' => $answers])
            ->assertSessionHasErrors('answers.comfort');
    }

    public function test_a_hidden_question_is_neither_shown_nor_required(): void
    {
        SurveyQuestion::forSurvey('pre')->where('key', 'source')->update(['active' => false]);

        $this->get(route('surveys.show', 'pre'))
            ->assertOk()
            ->assertDontSee('Where did you hear about this bootcamp?');

        $answers = $this->validPreAnswers();
        unset($answers['source']);

        $this->post(route('surveys.store', 'pre'), ['answers' => $answers])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, SurveyResponse::count());
    }

    public function test_the_thanks_page_needs_a_fresh_submission(): void
    {
        // Landing on it cold (refresh, bookmark) has nothing to thank anyone for.
        $this->get(route('surveys.thanks'))->assertRedirect(route('surveys.index'));

        $this->post(route('surveys.store', 'post'), ['answers' => ['expectations' => 4]]);

        $this->get(route('surveys.thanks'))
            ->assertOk()
            ->assertSee('All set');
    }

    public function test_the_post_survey_accepts_only_its_scale_question(): void
    {
        // Both free-text questions on the post survey are optional.
        $this->post(route('surveys.store', 'post'), ['answers' => ['expectations' => 5]])
            ->assertSessionHasNoErrors();

        $this->assertSame(5, SurveyResponse::forSurvey('post')->first()->answer('expectations'));
    }
}
