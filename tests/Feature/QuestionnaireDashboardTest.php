<?php

namespace Tests\Feature;

use App\Exports\QuestionnaireResponsesExport;
use App\Livewire\QuestionnaireQuestionsTable;
use App\Livewire\QuestionnaireResponsesTable;
use App\Livewire\QuestionnairesTable;
use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Building forms from the dashboard: creating them, adding fields, and the
 * rules that keep collected answers attached to the question that asked.
 */
class QuestionnaireDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function form(array $attributes = []): Questionnaire
    {
        return Questionnaire::create(array_merge([
            'title' => 'Mentor Application',
            'is_published' => true,
        ], $attributes));
    }

    protected function question(Questionnaire $form, array $attributes): QuestionnaireQuestion
    {
        return $form->questions()->create(array_merge([
            'type' => 'short_text',
            'is_required' => true,
            'is_active' => true,
        ], $attributes));
    }

    public function test_a_guest_cannot_reach_any_of_it(): void
    {
        $form = $this->form();

        $this->get(route('dashboard.questionnaires'))->assertRedirect(route('login'));
        $this->get(route('dashboard.questionnaires.build', $form))->assertRedirect(route('login'));
        $this->get(route('dashboard.questionnaires.responses', $form))->assertRedirect(route('login'));
        $this->post(route('dashboard.questionnaires'), ['title' => 'Sneaky'])->assertRedirect(route('login'));

        $this->assertSame(1, Questionnaire::count());
    }

    public function test_an_admin_creates_a_form_and_the_link_is_built_from_the_name(): void
    {
        $this->actingAs($this->admin())
            ->post(route('dashboard.questionnaires'), ['title' => 'Mentor Application 2026'])
            ->assertRedirect(route('dashboard.questionnaires'));

        $form = Questionnaire::sole();

        $this->assertSame('mentor-application-2026', $form->slug);
        // Unpublished by default: a form goes live when its questions are ready.
        $this->assertFalse($form->is_published);
    }

    public function test_a_chosen_link_is_kept_and_reserved_words_are_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('dashboard.questionnaires'), ['title' => 'Intake', 'slug' => 'spring-intake']);

        $this->assertSame('spring-intake', Questionnaire::sole()->slug);

        $this->actingAs($admin)
            ->post(route('dashboard.questionnaires'), ['title' => 'Other', 'slug' => 'thanks'])
            ->assertSessionHasErrors('slug');

        $this->actingAs($admin)
            ->post(route('dashboard.questionnaires'), ['title' => 'Other', 'slug' => 'Spring Intake'])
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Questionnaire::count());
    }

    public function test_a_closing_date_has_to_come_after_the_opening_one(): void
    {
        $this->actingAs($this->admin())
            ->post(route('dashboard.questionnaires'), [
                'title' => 'Intake',
                'opens_at' => now()->addWeek()->format('Y-m-d\TH:i'),
                'closes_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors('closes_at');

        // A closing date on its own is fine — there's nothing to come after.
        $this->actingAs($this->admin())
            ->post(route('dashboard.questionnaires'), [
                'title' => 'Intake',
                'closes_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Questionnaire::count());
    }

    public function test_an_admin_adds_each_kind_of_question(): void
    {
        $admin = $this->admin();
        $form = $this->form();

        $this->actingAs($admin)->post(route('dashboard.questionnaires.questions.store', $form), [
            'key' => 'track', 'label' => 'Track', 'type' => 'radio',
            'options' => "Data\nDesign\nDesign\n  ", 'is_required' => '1', 'is_active' => '1',
        ])->assertRedirect(route('dashboard.questionnaires'));

        $this->actingAs($admin)->post(route('dashboard.questionnaires.questions.store', $form), [
            'key' => 'days', 'label' => 'Days', 'type' => 'checkbox',
            'options' => "Mon\nTue", 'max_select' => 1, 'is_active' => '1',
        ]);

        $this->actingAs($admin)->post(route('dashboard.questionnaires.questions.store', $form), [
            'key' => 'cv', 'label' => 'CV', 'type' => 'file',
            'mimes' => 'PDF, .docx , pdf', 'max_kb' => 2048, 'is_active' => '1',
        ]);

        $track = $form->questions()->where('key', 'track')->sole();
        $days = $form->questions()->where('key', 'days')->sole();
        $cv = $form->questions()->where('key', 'cv')->sole();

        // Blank and duplicate lines are dropped, order is kept.
        $this->assertSame(['Data', 'Design'], $track->optionList());
        $this->assertSame(1, $days->maxSelect());
        $this->assertSame('pdf,docx', $cv->fileMimes());
        $this->assertSame(2048, $cv->fileMaxKb());
        // Added in the order they were created.
        $this->assertSame([1, 2, 3], $form->questions()->ordered()->pluck('sort_order')->all());
    }

    public function test_a_list_question_needs_at_least_two_options(): void
    {
        $form = $this->form();

        $this->actingAs($this->admin())
            ->post(route('dashboard.questionnaires.questions.store', $form), [
                'key' => 'track', 'label' => 'Track', 'type' => 'select', 'options' => 'Only one',
            ])
            ->assertSessionHasErrors('options');

        $this->assertSame(0, $form->questions()->count());
    }

    public function test_a_key_is_unique_within_a_form_but_reusable_across_forms(): void
    {
        $admin = $this->admin();
        $one = $this->form();
        $two = $this->form(['title' => 'Second form']);

        $this->question($one, ['key' => 'name', 'label' => 'Your name']);

        $this->actingAs($admin)
            ->post(route('dashboard.questionnaires.questions.store', $one), [
                'key' => 'name', 'label' => 'Name again', 'type' => 'short_text',
            ])
            ->assertSessionHasErrors('key');

        $this->actingAs($admin)
            ->post(route('dashboard.questionnaires.questions.store', $two), [
                'key' => 'name', 'label' => 'Your name', 'type' => 'short_text',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $one->questions()->count());
        $this->assertSame(1, $two->questions()->count());
    }

    public function test_editing_a_question_cannot_move_its_key_or_its_form(): void
    {
        $form = $this->form();
        $other = $this->form(['title' => 'Second form']);
        $question = $this->question($form, ['key' => 'name', 'label' => 'Your name']);

        $this->actingAs($this->admin())
            ->put(route('dashboard.questionnaires.questions.update', $question), [
                'key' => 'renamed',
                'questionnaire_id' => $other->id,
                'label' => 'What is your name?',
                'type' => 'short_text',
                'is_required' => '1',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $question->refresh();

        $this->assertSame('name', $question->key);
        $this->assertSame($form->id, $question->questionnaire_id);
        $this->assertSame('What is your name?', $question->label);
    }

    public function test_hiding_a_question_keeps_the_answers_it_collected(): void
    {
        $form = $this->form();
        $question = $this->question($form, ['key' => 'name', 'label' => 'Your name']);

        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama']]);

        $this->actingAs($this->admin())
            ->delete(route('dashboard.questionnaires.questions.destroy', $question))
            ->assertRedirect(route('dashboard.questionnaires.build', $form));

        $this->assertSame(0, $form->questions()->count());
        $this->assertSame('Ama', QuestionnaireResponse::sole()->answer('name'));
    }

    public function test_duplicating_copies_the_questions_and_never_the_responses(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);
        $this->question($form, ['key' => 'track', 'label' => 'Track', 'type' => 'radio', 'options' => ['Data', 'Design']]);
        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama', 'track' => 'Data']]);

        $this->actingAs($this->admin())
            ->post(route('dashboard.questionnaires.duplicate', $form));

        $copy = Questionnaire::where('id', '!=', $form->id)->sole();

        $this->assertSame('Mentor Application (copy)', $copy->title);
        $this->assertSame('mentor-application-copy', $copy->slug);
        $this->assertFalse($copy->is_published);
        $this->assertSame(2, $copy->questions()->count());
        $this->assertSame(0, $copy->responses()->count());
        $this->assertSame(1, $form->responses()->count());
    }

    public function test_a_form_with_responses_cannot_be_deleted_but_one_without_can(): void
    {
        $admin = $this->admin();
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);
        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama']]);

        $this->actingAs($admin)
            ->delete(route('dashboard.questionnaires.destroy', $form))
            ->assertSessionHas('error');

        $this->assertSame(1, Questionnaire::count());

        $empty = $this->form(['title' => 'Nothing collected']);
        $this->question($empty, ['key' => 'name', 'label' => 'Your name']);

        $this->actingAs($admin)
            ->delete(route('dashboard.questionnaires.destroy', $empty))
            ->assertSessionHas('status');

        $this->assertSame(1, Questionnaire::count());
        $this->assertSame(0, QuestionnaireQuestion::where('questionnaire_id', $empty->id)->count());
    }

    public function test_unpublishing_closes_the_link_and_keeps_the_responses(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);
        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama']]);

        $this->actingAs($this->admin())
            ->put(route('dashboard.questionnaires.update', $form), ['title' => $form->title]);

        $this->assertFalse($form->refresh()->is_published);
        $this->get(route('forms.show', $form))->assertNotFound();

        $this->actingAs($this->admin())
            ->get(route('dashboard.questionnaires.responses', $form))
            ->assertOk()
            ->assertSee('Responses');

        $this->assertSame(1, $form->responses()->count());
    }

    public function test_the_dashboard_tables_show_what_was_collected(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);
        $this->question($form, ['key' => 'days', 'label' => 'Days', 'type' => 'checkbox', 'options' => ['Mon', 'Tue']]);
        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama Serwaa', 'days' => ['Mon', 'Tue']]]);

        $this->actingAs($this->admin());

        Livewire::test(QuestionnairesTable::class)
            ->assertSee('Mentor Application')
            ->assertSee('/forms/mentor-application')
            ->assertSee('Open');

        Livewire::test(QuestionnaireQuestionsTable::class, ['questionnaireId' => $form->id])
            ->assertSee('Your name')
            ->assertSee('Mon · Tue');

        Livewire::test(QuestionnaireResponsesTable::class, ['questionnaireId' => $form->id])
            ->assertSee('Ama Serwaa')
            ->assertSee('Mon, Tue');
    }

    public function test_the_responses_table_only_shows_its_own_form(): void
    {
        $one = $this->form();
        $two = $this->form(['title' => 'Second form']);
        $this->question($one, ['key' => 'name', 'label' => 'Your name']);
        $this->question($two, ['key' => 'name', 'label' => 'Your name']);

        $this->post(route('forms.store', $one), ['answers' => ['name' => 'From form one']]);
        $this->post(route('forms.store', $two), ['answers' => ['name' => 'From form two']]);

        $this->actingAs($this->admin());

        Livewire::test(QuestionnaireResponsesTable::class, ['questionnaireId' => $one->id])
            ->assertSee('From form one')
            ->assertDontSee('From form two');
    }

    public function test_a_response_and_its_upload_can_be_removed_together(): void
    {
        Storage::fake('local');

        $form = $this->form();
        $this->question($form, ['key' => 'cv', 'label' => 'CV', 'type' => 'file']);

        $this->post(route('forms.store', $form), [
            'uploads' => ['cv' => UploadedFile::fake()->create('ama-cv.pdf', 60, 'application/pdf')],
        ]);

        $response = QuestionnaireResponse::sole();
        $path = $response->files()->sole()->path;

        $this->actingAs($this->admin())
            ->delete(route('dashboard.questionnaires.response.destroy', $response))
            ->assertRedirect(route('dashboard.questionnaires.responses', $form));

        $this->assertSame(0, QuestionnaireResponse::count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_an_upload_only_downloads_for_a_signed_in_admin(): void
    {
        Storage::fake('local');

        $form = $this->form();
        $this->question($form, ['key' => 'cv', 'label' => 'CV', 'type' => 'file']);
        $this->post(route('forms.store', $form), [
            'uploads' => ['cv' => UploadedFile::fake()->create('ama-cv.pdf', 60, 'application/pdf')],
        ]);

        $file = QuestionnaireResponse::sole()->files()->sole();

        $this->get(route('dashboard.questionnaires.file', $file))->assertRedirect(route('login'));

        $this->actingAs($this->admin())
            ->get(route('dashboard.questionnaires.file', $file))
            ->assertOk()
            ->assertDownload('ama-cv.pdf');
    }

    public function test_the_export_carries_every_question_including_hidden_ones(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);
        $this->question($form, ['key' => 'days', 'label' => 'Days', 'type' => 'checkbox', 'options' => ['Mon', 'Tue']]);
        $this->post(route('forms.store', $form), ['answers' => ['name' => 'Ama', 'days' => ['Mon']]]);

        $form->questions()->where('key', 'days')->update(['is_active' => false]);

        $export = new QuestionnaireResponsesExport($form->fresh());

        $this->assertSame(['Reference', 'Form', 'Submitted', 'Your name', 'Days'], $export->headings());
        $this->assertSame(['Ama', 'Mon'], array_slice($export->map(QuestionnaireResponse::sole()), 3));

        $this->actingAs($this->admin())
            ->get(route('dashboard.questionnaires.responses.export', $form))
            ->assertOk();
    }

    public function test_the_builder_page_shows_the_share_link(): void
    {
        $form = $this->form();
        $this->question($form, ['key' => 'name', 'label' => 'Your name']);

        $this->actingAs($this->admin())
            ->get(route('dashboard.questionnaires.build', $form))
            ->assertOk()
            ->assertSee(route('forms.show', $form))
            ->assertSee('Share this form');
    }

    /**
     * The create/edit forms are fetched into the shared admin modal over AJAX,
     * so they only ever render through that path — worth exercising.
     */
    public function test_the_modal_forms_render_over_ajax(): void
    {
        $ajax = ['X-Requested-With' => 'XMLHttpRequest'];
        $form = $this->form();
        $question = $this->question($form, [
            'key' => 'track', 'label' => 'Track', 'type' => 'radio', 'options' => ['Data', 'Design'],
        ]);
        $this->post(route('forms.store', $form), ['answers' => ['track' => 'Data']]);

        $this->actingAs($this->admin());

        $this->get(route('dashboard.questionnaires'))->assertOk()->assertSee('New Form');

        $this->get(route('dashboard.questionnaires.edit', $form), $ajax)
            ->assertOk()->assertSee('Thank-you message');

        $this->get(route('dashboard.questionnaires.questions.create', $form), $ajax)
            ->assertOk()->assertSee('Answer type')->assertSee('Allowed file types');

        // Existing options come back one per line, ready to edit.
        $this->get(route('dashboard.questionnaires.questions.edit', $question), $ajax)
            ->assertOk()->assertSee("Data\nDesign", false);

        $response = QuestionnaireResponse::sole();

        $this->get(route('dashboard.questionnaires.response', $response), $ajax)
            ->assertOk()->assertSee($response->reference);
    }
}
