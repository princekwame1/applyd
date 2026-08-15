<?php

namespace Tests\Feature;

use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The public half: one link, every field type, and the rules that stop a form
 * accepting something its admin never offered.
 */
class QuestionnaireFormTest extends TestCase
{
    use RefreshDatabase;

    protected function form(array $attributes = []): Questionnaire
    {
        return Questionnaire::create(array_merge([
            'title' => 'Mentor Application',
            'description' => 'Tell us about yourself.',
            'is_published' => true,
        ], $attributes));
    }

    protected function question(Questionnaire $form, array $attributes): QuestionnaireQuestion
    {
        return $form->questions()->create(array_merge([
            'type' => 'short_text',
            'is_required' => true,
            'is_active' => true,
            'sort_order' => $form->questions()->count() + 1,
        ], $attributes));
    }

    public function test_a_draft_form_is_not_a_public_page(): void
    {
        $form = $this->form(['is_published' => false]);
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);

        $this->get(route('forms.show', $form))->assertNotFound();
        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama']])->assertNotFound();

        $this->assertSame(0, QuestionnaireResponse::count());
    }

    public function test_a_published_form_with_nothing_to_ask_is_not_a_public_page(): void
    {
        $form = $this->form();

        $this->get(route('forms.show', $form))->assertNotFound();

        $this->question($form, ['key' => 'name', 'label' => 'Your name', 'is_active' => false]);

        $this->get(route('forms.show', $form))->assertNotFound();
    }

    public function test_an_unknown_link_is_a_404(): void
    {
        $this->get(url('/forms/nothing-here'))->assertNotFound();
    }

    public function test_every_field_type_renders_its_own_control(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name', 'type' => 'short_text']);
        $this->question($form, ['key' => 'about', 'label' => 'About you', 'type' => 'long_text']);
        $this->question($form, ['key' => 'track', 'label' => 'Track', 'type' => 'radio', 'options' => ['Data', 'Design']]);
        $this->question($form, ['key' => 'days', 'label' => 'Days', 'type' => 'checkbox', 'options' => ['Mon', 'Tue']]);
        $this->question($form, ['key' => 'level', 'label' => 'Level', 'type' => 'select', 'options' => ['Junior', 'Senior']]);
        $this->question($form, ['key' => 'cv', 'label' => 'Your CV', 'type' => 'file']);

        $response = $this->get(route('forms.show', $form));

        $response->assertOk()
            ->assertSee('Mentor Application')
            ->assertSee('name="answers[name]"', false)
            ->assertSee('<textarea id="q_about" name="answers[about]"', false)
            ->assertSee('type="radio" id="q_track" name="answers[track]" value="Data"', false)
            ->assertSee('type="checkbox" id="q_days" name="answers[days][]" value="Mon"', false)
            ->assertSee('<select id="q_level" name="answers[level]"', false)
            ->assertSee('type="file" id="q_cv" name="uploads[cv]"', false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_a_submission_is_stored_and_the_visitor_gets_a_reference(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);
        $this->question($form, ['key' => 'track', 'label' => 'Track', 'type' => 'radio', 'options' => ['Data', 'Design']]);
        $this->question($form, ['key' => 'days', 'label' => 'Days', 'type' => 'checkbox', 'options' => ['Mon', 'Tue', 'Wed']]);

        $this->post(route('forms.store', $form), [
            'answers' => [
                'name' => '  Ama Serwaa  ',
                'track' => 'Design',
                'days' => ['Mon', 'Wed'],
            ],
        ])->assertRedirect(route('forms.thanks', $form));

        $response = QuestionnaireResponse::sole();

        $this->assertSame($form->id, $response->questionnaire_id);
        $this->assertSame('Ama Serwaa', $response->answer('name'));
        $this->assertSame('Design', $response->answer('track'));
        $this->assertSame(['Mon', 'Wed'], $response->answer('days'));
        $this->assertNotEmpty($response->reference);

        $this->followingRedirects()
            ->post(route('forms.store', $form), [
                'answers' => ['name' => 'Kofi', 'track' => 'Data', 'days' => ['Tue']],
            ])
            ->assertSee('Thank you');
    }

    public function test_the_thanks_page_needs_a_fresh_submission(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);

        $this->get(route('forms.thanks', $form))->assertRedirect(route('forms.show', $form));
    }

    public function test_a_missing_required_answer_stores_nothing(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);

        $this->post(route('forms.store', $form), ['answers' => ['name' => '']])
            ->assertSessionHasErrors('answers.name');

        $this->assertSame(0, QuestionnaireResponse::count());
    }

    public function test_a_skipped_optional_answer_is_left_out_of_the_map(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);
        $this->question($form, ['key' => 'notes', 'label' => 'Anything else', 'type' => 'long_text', 'is_required' => false]);

        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama']]);

        $this->assertSame(['name' => 'Ama'], QuestionnaireResponse::sole()->answers);
    }

    public function test_an_option_the_admin_never_offered_is_rejected(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'track', 'label' => 'Track', 'type' => 'radio', 'options' => ['Data', 'Design']]);

        $this->post(route('forms.store', $form), ['answers' => ['track' => 'Something else']])
            ->assertSessionHasErrors('answers.track');

        $this->post(route('forms.store', $form), ['answers' => ['track' => ['Data']]])
            ->assertSessionHasErrors('answers.track');

        $this->assertSame(0, QuestionnaireResponse::count());
    }

    public function test_a_checkbox_question_enforces_its_ceiling(): void
    {
        $form = $this->form();
        $this->question($form, [
            'key' => 'days', 'label' => 'Days', 'type' => 'checkbox',
            'options' => ['Mon', 'Tue', 'Wed'], 'settings' => ['max_select' => 2],
        ]);

        $this->post(route('forms.store', $form), ['answers' => ['days' => ['Mon', 'Tue', 'Wed']]])
            ->assertSessionHasErrors('answers.days');

        $this->post(route('forms.store', $form), ['answers' => ['days' => ['Mon', 'Nope']]])
            ->assertSessionHasErrors('answers.days.1');

        $this->assertSame(0, QuestionnaireResponse::count());
    }

    public function test_a_hidden_question_is_neither_asked_nor_required(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);
        $this->question($form, ['key' => 'secret', 'label' => 'Retired question', 'is_active' => false]);

        $this->get(route('forms.show', $form))->assertOk()->assertDontSee('Retired question');

        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama']])
            ->assertSessionHasNoErrors();

        $this->assertArrayNotHasKey('secret', QuestionnaireResponse::sole()->answers);
    }

    public function test_an_upload_lands_on_the_private_disk_and_never_the_public_one(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $form = $this->form();
        $this->question($form, ['key' => 'cv', 'label' => 'Your CV', 'type' => 'file']);

        $this->post(route('forms.store', $form), [
            'uploads' => ['cv' => UploadedFile::fake()->create('ama-cv.pdf', 120, 'application/pdf')],
        ])->assertRedirect(route('forms.thanks', $form));

        $response = QuestionnaireResponse::sole();
        $file = $response->files()->sole();

        $this->assertSame('cv', $file->question_key);
        $this->assertSame('ama-cv.pdf', $file->original_name);
        $this->assertStringStartsWith('questionnaires/'.$form->id.'/', $file->path);
        Storage::disk('local')->assertExists($file->path);
        Storage::disk('public')->assertDirectoryEmpty('/');

        // Mirrored into the answers map so every read path sees one shape.
        $this->assertSame('ama-cv.pdf', $response->answer('cv'));
    }

    public function test_an_upload_of_the_wrong_type_or_size_is_refused(): void
    {
        Storage::fake('local');

        $form = $this->form();
        $this->question($form, [
            'key' => 'cv', 'label' => 'Your CV', 'type' => 'file',
            'settings' => ['mimes' => 'pdf', 'max_kb' => 100],
        ]);

        $this->post(route('forms.store', $form), [
            'uploads' => ['cv' => UploadedFile::fake()->create('sneaky.php', 10, 'text/x-php')],
        ])->assertSessionHasErrors('uploads.cv');

        $this->post(route('forms.store', $form), [
            'uploads' => ['cv' => UploadedFile::fake()->create('huge.pdf', 900, 'application/pdf')],
        ])->assertSessionHasErrors('uploads.cv');

        $this->assertSame(0, QuestionnaireResponse::count());
        $this->assertEmpty(Storage::disk('local')->allFiles());
    }

    public function test_a_form_outside_its_window_says_so_instead_of_taking_answers(): void
    {
        $form = $this->form(['closes_at' => now()->subDay()]);
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);

        $this->get(route('forms.show', $form))
            ->assertOk()
            ->assertSee('This form closed on')
            ->assertDontSee('name="answers[name]"', false);

        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama']])
            ->assertRedirect(route('forms.show', $form));

        $this->assertSame(0, QuestionnaireResponse::count());
    }

    public function test_a_form_that_has_not_opened_yet_is_held_back(): void
    {
        $form = $this->form(['opens_at' => now()->addWeek()]);
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);

        $this->get(route('forms.show', $form))->assertOk()->assertSee('This form opens on');
        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama']]);

        $this->assertSame(0, QuestionnaireResponse::count());
    }

    public function test_a_form_closes_itself_once_the_response_limit_is_reached(): void
    {
        $form = $this->form(['response_limit' => 1]);
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);

        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama']]);
        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Kofi']])
            ->assertRedirect(route('forms.show', $form));

        $this->assertSame(1, QuestionnaireResponse::count());
        $this->get(route('forms.show', $form))->assertSee('reached its limit');
    }
}
