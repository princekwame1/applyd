<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\JobScreeningCriterion as Criterion;
use App\Models\User;
use App\Support\CvText;
use App\Support\FitScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Pre-screening, and the viewer that saves the recruiter a download.
 *
 * The rules worth holding on to: a criterion nobody was asked never counts
 * against a candidate, a missed must-have flags but never rejects, the
 * keywords a CV is scored against are never shown to the applicant, and the
 * viewer is behind exactly the same two gates as the download it replaces.
 */
class JobScreeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function company(string $name = 'Acme Ltd'): Company
    {
        $user = User::factory()->create();
        $user->assignRole('company');

        return Company::create([
            'user_id' => $user->id,
            'name' => $name,
            'status' => Company::APPROVED,
        ]);
    }

    private function job(Company $company): JobOpening
    {
        return $company->openings()->create([
            'title' => 'Laravel Developer',
            'description' => 'Build things.',
            'type' => 'Full-time',
            'sector' => 'Information Technology',
            'is_open' => true,
            'status' => JobOpening::APPROVED,
        ]);
    }

    private function buyPlan(Company $company): void
    {
        $company->purchases()->create([
            'plan_name' => 'Starter',
            'credits' => 5,
            'amount' => 100,
            'reference' => 'TEST-'.$company->id.'-'.uniqid(),
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    private function keyword(JobOpening $opening, string $label, array $extra = []): Criterion
    {
        return $opening->screeningCriteria()->create(array_merge([
            'kind' => Criterion::KEYWORD,
            'label' => $label,
            'weight' => 1,
        ], $extra));
    }

    private function question(JobOpening $opening, string $label, array $extra = []): Criterion
    {
        return $opening->screeningCriteria()->create(array_merge([
            'kind' => Criterion::QUESTION,
            'key' => Criterion::uniqueKey($opening->id, $label),
            'label' => $label,
            'answer_type' => Criterion::YES_NO,
            'expected' => ['yes'],
            'weight' => 1,
        ], $extra));
    }

    private function applyTo(JobOpening $opening, array $payload = []): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('jobs.apply', $opening), array_merge([
            'full_name' => 'Kwame Asante',
            'email' => 'kwame@example.com',
            'cover_letter' => 'I have shipped Laravel apps for four years.',
            'cv' => UploadedFile::fake()->create('kwame-cv.pdf', 40, 'application/pdf'),
        ], $payload));
    }

    /* ------------------------------------------------------------- scoring */

    public function test_a_keyword_is_matched_against_the_cover_letter(): void
    {
        $opening = $this->job($this->company());
        $this->keyword($opening, 'Laravel');
        $this->keyword($opening, 'Kubernetes');

        $this->applyTo($opening)->assertRedirect();

        $application = JobApplication::first();

        $this->assertSame(50, $application->fit_score);
        $this->assertSame(['Kubernetes'], $application->missedLabels());
    }

    public function test_a_keyword_matches_whole_words_only(): void
    {
        // The reason boundaries are there at all: "Java" must not be found
        // inside "JavaScript", or every front-end CV scores as a Java one.
        $this->assertTrue(FitScore::mentions('Strong Java and SQL', 'Java'));
        $this->assertFalse(FitScore::mentions('Five years of JavaScript', 'Java'));

        // And why they cannot be there unconditionally: these end in
        // punctuation, where a word boundary would never match.
        $this->assertTrue(FitScore::mentions('Wrote C++ for embedded work', 'C++'));
        $this->assertTrue(FitScore::mentions('Built on .NET Core', '.NET'));
    }

    public function test_weights_count_and_answers_are_scored(): void
    {
        $opening = $this->job($this->company());
        $this->question($opening, 'Do you have a valid driver’s licence?', ['weight' => 4]);
        $this->keyword($opening, 'Kubernetes');

        $licence = $opening->screeningQuestions()->first();

        $this->applyTo($opening, ['screening' => [$licence->key => 'yes']])->assertRedirect();

        // 4 of 5 possible weight — the keyword is missed, the answer carries.
        $this->assertSame(80, JobApplication::first()->fit_score);
    }

    public function test_a_missed_must_have_flags_the_applicant_without_refusing_them(): void
    {
        $opening = $this->job($this->company());
        $licence = $this->question($opening, 'Do you have a valid driver’s licence?', ['is_knockout' => true]);

        $this->applyTo($opening, ['screening' => [$licence->key => 'no']])
            ->assertRedirect()
            ->assertSessionHas('applied');

        $application = JobApplication::first();

        // Still in the list, still readable, simply marked.
        $this->assertTrue($application->missesRequirement());
        $this->assertSame(0, $application->fit_score);
        $this->assertDatabaseCount('job_applications', 1);
    }

    public function test_a_criterion_added_later_is_not_counted_against_earlier_applicants(): void
    {
        $company = $this->company();
        $opening = $this->job($company);
        $this->keyword($opening, 'Laravel');

        $this->applyTo($opening);
        $this->assertSame(100, JobApplication::first()->fit_score);

        // A question nobody was asked. Unknown, not missed: it is left out of
        // the sum entirely rather than dragging a full score down.
        $this->question($opening, 'Can you start in October?');
        FitScore::rescore($opening->fresh());

        $application = JobApplication::first();

        $this->assertSame(100, $application->fit_score);
        $this->assertSame([], $application->missedLabels());
        $this->assertContains('unknown', collect($application->fit_breakdown)->pluck('result')->all());
    }

    public function test_a_job_with_no_criteria_scores_nobody(): void
    {
        $opening = $this->job($this->company());

        $this->applyTo($opening);

        $application = JobApplication::first();

        // Null, never zero — zero would read as "matched none of it".
        $this->assertNull($application->fit_score);
        $this->assertNull($application->meets_requirements);
        $this->assertFalse($application->isScored());
    }

    public function test_an_answer_to_a_question_this_job_never_asked_is_dropped(): void
    {
        $opening = $this->job($this->company());
        $mine = $this->question($opening, 'Can you start in October?');

        $this->applyTo($opening, ['screening' => [$mine->key => 'yes', 'smuggled' => 'yes']]);

        $this->assertSame([$mine->key => 'yes'], JobApplication::first()->screening_answers);
    }

    /* -------------------------------------------------------- the apply form */

    public function test_questions_are_asked_on_the_form_and_keywords_are_never_shown(): void
    {
        $opening = $this->job($this->company());
        $this->question($opening, 'Can you start in October?');
        $this->keyword($opening, 'Kubernetes');

        $response = $this->get(route('jobs.show', $opening));

        $response->assertSee('Can you start in October?', false);
        // The rubric is the recruiter's. Publishing it would just teach
        // applicants which words to paste in.
        $response->assertDontSee('Kubernetes');
    }

    public function test_a_question_that_is_not_answered_is_rejected(): void
    {
        $opening = $this->job($this->company());
        $this->question($opening, 'Can you start in October?');

        $this->applyTo($opening)->assertSessionHasErrors();
        $this->assertDatabaseCount('job_applications', 0);
    }

    public function test_an_answer_outside_the_offered_options_is_rejected(): void
    {
        $opening = $this->job($this->company());
        $level = $this->question($opening, 'Highest qualification?', [
            'answer_type' => Criterion::CHOICE,
            'options' => ['Degree', 'HND'],
            'expected' => ['Degree'],
        ]);

        $this->applyTo($opening, ['screening' => [$level->key => 'Doctorate']])
            ->assertSessionHasErrors('screening.'.$level->key);
    }

    /* ------------------------------------------------------ the criteria screen */

    public function test_a_recruiter_sets_criteria_without_a_plan_and_everyone_is_rescored(): void
    {
        $company = $this->company();
        $opening = $this->job($company);

        $this->applyTo($opening);
        $this->assertNull(JobApplication::first()->fit_score);

        // Free, like posting the job. The plan gates reading the applicants.
        $this->actingAs($company->user)
            ->post(route('company.screening.store', $opening), [
                'kind' => Criterion::KEYWORD,
                'label' => 'Laravel',
                'weight' => 2,
            ])
            ->assertRedirect(route('company.screening', $opening));

        $this->assertSame(100, JobApplication::first()->fit_score);
    }

    public function test_a_number_question_passes_at_or_above_its_minimum(): void
    {
        $company = $this->company();
        $opening = $this->job($company);

        $this->actingAs($company->user)->post(route('company.screening.store', $opening), [
            'kind' => Criterion::QUESTION,
            'answer_type' => Criterion::NUMBER,
            'label' => 'Years of experience',
            'minimum' => 3,
            'weight' => 1,
        ]);

        $years = $opening->screeningQuestions()->first();

        $this->applyTo($opening, ['screening' => [$years->key => 2]]);
        $this->assertSame(0, JobApplication::first()->fit_score);

        $this->applyTo($opening, [
            'email' => 'ama@example.com',
            'screening' => [$years->key => 3],
        ]);
        $this->assertSame(100, JobApplication::where('email', 'ama@example.com')->first()->fit_score);
    }

    public function test_an_accepted_answer_has_to_be_one_of_the_options(): void
    {
        $company = $this->company();
        $opening = $this->job($company);

        // A pass mark pointing at an answer nobody can give could never be met.
        $this->actingAs($company->user)
            ->post(route('company.screening.store', $opening), [
                'kind' => Criterion::QUESTION,
                'answer_type' => Criterion::CHOICE,
                'label' => 'Highest qualification?',
                'choices' => "Degree\nHND",
                'accepted' => 'Doctorate',
                'weight' => 1,
            ])
            ->assertSessionHasErrors('accepted');

        $this->assertDatabaseCount('job_screening_criteria', 0);
    }

    public function test_editing_a_criterion_cannot_move_the_key_answers_are_filed_under(): void
    {
        $company = $this->company();
        $opening = $this->job($company);
        $question = $this->question($opening, 'Can you start in October?');

        $this->actingAs($company->user)->put(route('company.screening.update', [$opening, $question]), [
            'label' => 'Can you start in November?',
            'weight' => 3,
            'kind' => Criterion::KEYWORD,
            'answer_type' => Criterion::NUMBER,
        ]);

        $question->refresh();

        $this->assertSame('Can you start in November?', $question->label);
        $this->assertSame(3, $question->weight);
        // The handle every collected answer is filed under, and the shape it
        // was collected in, both stay put.
        $this->assertSame('can_you_start_in_october', $question->key);
        $this->assertSame(Criterion::QUESTION, $question->kind);
        $this->assertSame(Criterion::YES_NO, $question->answer_type);
    }

    public function test_another_company_cannot_touch_your_criteria(): void
    {
        $mine = $this->job($this->company());
        $theirs = $this->company('Rival Ltd');

        $this->actingAs($theirs->user)->get(route('company.screening', $mine))->assertForbidden();
        $this->actingAs($theirs->user)->post(route('company.screening.store', $mine), [
            'kind' => Criterion::KEYWORD, 'label' => 'Laravel', 'weight' => 1,
        ])->assertForbidden();
    }

    /* ------------------------------------------------------- the applicants list */

    public function test_the_list_puts_the_best_fit_first_and_filters_to_top_matches(): void
    {
        $company = $this->company();
        $this->buyPlan($company);
        $opening = $this->job($company);
        $this->keyword($opening, 'Laravel');

        $this->applyTo($opening, ['full_name' => 'Weak Match', 'email' => 'weak@example.com', 'cover_letter' => 'I type fast.']);
        $this->applyTo($opening, ['full_name' => 'Strong Match', 'email' => 'strong@example.com', 'cover_letter' => 'Four years of Laravel.']);

        $response = $this->actingAs($company->user)->get(route('company.applications', $opening));

        $response->assertSeeInOrder(['Strong Match', 'Weak Match']);

        $this->actingAs($company->user)
            ->get(route('company.applications', ['opening' => $opening, 'fit' => 'top']))
            ->assertSee('Strong Match')
            ->assertDontSee('Weak Match');
    }

    /* ------------------------------------------------------------- the viewer */

    public function test_a_pdf_is_streamed_for_viewing_in_place(): void
    {
        $company = $this->company();
        $this->buyPlan($company);
        $opening = $this->job($company);
        $this->applyTo($opening);

        $response = $this->actingAs($company->user)
            ->get(route('company.applications.cv.view', JobApplication::first()));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    public function test_a_word_file_is_shown_as_text_rather_than_a_blank_frame(): void
    {
        $company = $this->company();
        $this->buyPlan($company);
        $opening = $this->job($company);

        $this->applyTo($opening, ['cv' => $this->docx('Kwame Asante. Laravel and MySQL developer, four years in Accra.')]);

        $this->actingAs($company->user)
            ->get(route('company.applications.cv.view', JobApplication::first()))
            ->assertOk()
            ->assertSee('Laravel and MySQL developer', false);
    }

    public function test_the_viewer_is_behind_the_same_two_gates_as_the_download(): void
    {
        $company = $this->company();
        $opening = $this->job($company);
        $this->applyTo($opening);
        $application = JobApplication::first();

        // No plan: sent to buy one, exactly like the list and the download.
        $this->actingAs($company->user)
            ->get(route('company.applications.cv.view', $application))
            ->assertRedirect(route('company.plans'));

        // Somebody else's applicant: nothing to buy that would make it theirs.
        $rival = $this->company('Rival Ltd');
        $this->buyPlan($rival);

        $this->actingAs($rival->user)
            ->get(route('company.applications.cv.view', $application))
            ->assertForbidden();
    }

    /* --------------------------------------------------------- reading a CV */

    public function test_text_is_read_out_of_a_docx_and_scored(): void
    {
        $opening = $this->job($this->company());
        $this->keyword($opening, 'Laravel');
        $this->keyword($opening, 'Kubernetes');

        $this->applyTo($opening, [
            'cover_letter' => null,
            'cv' => $this->docx('Kwame Asante — Laravel developer, four years in Accra.'),
        ]);

        $application = JobApplication::first();

        $this->assertStringContainsString('Laravel developer', $application->cv_text);
        $this->assertSame(50, $application->fit_score);
    }

    public function test_a_cv_that_will_not_open_leaves_its_keywords_unchecked(): void
    {
        // A scanned CV is a picture. Scoring it zero would bury a candidate
        // for something they did not do, so it is reported as unread instead.
        $this->assertNull(CvText::fromString('not really a pdf', 'scan.pdf'));

        $opening = $this->job($this->company());
        $this->keyword($opening, 'Laravel');

        $this->applyTo($opening, [
            'cover_letter' => null,
            'cv' => UploadedFile::fake()->create('scan.pdf', 20, 'application/pdf'),
        ]);

        $application = JobApplication::first();

        $this->assertNull($application->cv_text);
        $this->assertNull($application->fit_score);
        $this->assertSame('unknown', $application->fit_breakdown[0]['result']);
    }

    /** A real .docx: a zip with the body XML inside it. */
    private function docx(string $body): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'cv').'.docx';

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml',
            '<?xml version="1.0"?><w:document xmlns:w="x"><w:body><w:p><w:r><w:t>'
            .htmlspecialchars($body, ENT_XML1)
            .'</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();

        return new UploadedFile($path, 'kwame-cv.docx', null, null, true);
    }
}
