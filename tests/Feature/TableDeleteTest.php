<?php

namespace Tests\Feature;

use App\Livewire\CourseEnrollmentsTable;
use App\Livewire\EmailLogsTable;
use App\Livewire\FinanceCategoriesTable;
use App\Livewire\QuestionnaireResponsesTable;
use App\Livewire\RolesTable;
use App\Livewire\SmsLogsTable;
use App\Livewire\SurveysTable;
use App\Livewire\UsersTable;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\EmailLog;
use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\Questionnaire;
use App\Models\QuestionnaireResponse;
use App\Models\SmsLog;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Services\StudentAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Deleting from the dashboard tables.
 *
 * The behaviour is shared (App\Livewire\Concerns\WithRowDelete), so the trait's
 * own rules are checked once and each table is then checked for the thing that
 * is specific to it — the row it must refuse, or the file it has to clean up.
 */
class TableDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    /* ------------------------------------------------------------ the trait */

    public function test_a_row_can_be_deleted_from_a_table(): void
    {
        $log = SmsLog::create(['phone_number' => '+233240000000', 'message' => 'hi', 'status' => 'sent']);

        Livewire::actingAs($this->admin())
            ->test(SmsLogsTable::class)
            ->call('performDelete', $log->id);

        $this->assertSame(0, SmsLog::count());
    }

    public function test_deleting_asks_before_it_does_anything(): void
    {
        $log = SmsLog::create(['phone_number' => '+233240000000', 'message' => 'hi', 'status' => 'sent']);

        // deleteRow only raises the dialog; the perform* method does the work.
        Livewire::actingAs($this->admin())
            ->test(SmsLogsTable::class)
            ->call('deleteRow', $log->id);

        $this->assertSame(1, SmsLog::count());
    }

    public function test_a_batch_can_be_deleted(): void
    {
        $ids = collect(range(1, 3))
            ->map(fn ($i) => SmsLog::create([
                'phone_number' => '+23324000000'.$i, 'message' => 'hi', 'status' => 'sent',
            ])->id)
            ->map(fn ($id) => (string) $id)
            ->all();

        Livewire::actingAs($this->admin())
            ->test(SmsLogsTable::class)
            ->set('selected', $ids)
            ->call('performDeleteSelected');

        $this->assertSame(0, SmsLog::count());
    }

    public function test_deleting_is_refused_for_anyone_without_the_permission(): void
    {
        $log = SmsLog::create(['phone_number' => '+233240000000', 'message' => 'hi', 'status' => 'sent']);

        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        // Livewire methods are callable over HTTP by whoever can reach them, so
        // the gate has to be in the component, not only on the page.
        Livewire::actingAs($outsider)
            ->test(SmsLogsTable::class)
            ->call('performDelete', $log->id)
            ->assertForbidden();

        $this->assertSame(1, SmsLog::count());
    }

    public function test_an_email_log_can_be_deleted_without_touching_anything_else(): void
    {
        EmailLog::create([
            'email' => 'ama@example.com', 'subject' => 'Hi', 'body' => '<p>Hi</p>', 'status' => 'sent',
        ]);

        Livewire::actingAs($this->admin())
            ->test(EmailLogsTable::class)
            ->call('performDelete', EmailLog::sole()->id);

        $this->assertSame(0, EmailLog::count());
    }

    /* --------------------------------------------------- the per-table rules */

    public function test_a_survey_with_responses_is_refused_in_bulk_as_well(): void
    {
        $kept = Survey::create(['name' => 'Has answers', 'slug' => 'has-answers']);
        $goes = Survey::create(['name' => 'Empty one', 'slug' => 'empty-one']);
        SurveyResponse::create(['survey_id' => $kept->id, 'answers' => ['a' => 'b']]);

        Livewire::actingAs($this->admin())
            ->test(SurveysTable::class)
            ->set('selected', [(string) $kept->id, (string) $goes->id])
            ->call('performDeleteSelected');

        // The guard in SurveyManagerController is worthless if ticking the row
        // goes around it.
        $this->assertTrue(Survey::whereKey($kept->id)->exists());
        $this->assertFalse(Survey::whereKey($goes->id)->exists());
    }

    public function test_a_finance_category_in_use_is_refused(): void
    {
        $category = FinanceCategory::create(['name' => 'Venue', 'type' => 'expense']);
        FinanceTransaction::create([
            'type' => 'expense', 'amount' => '10.00', 'occurred_on' => now()->subDay(),
            'finance_category_id' => $category->id,
        ]);

        Livewire::actingAs($this->admin())
            ->test(FinanceCategoriesTable::class)
            ->call('performDelete', $category->id);

        $this->assertTrue(FinanceCategory::whereKey($category->id)->exists());
    }

    public function test_you_cannot_delete_your_own_account_or_the_last_super(): void
    {
        $admin = $this->admin();
        $admin->givePermissionTo('manage users');

        $super = User::factory()->create();
        $super->assignRole('super');

        Livewire::actingAs($admin)
            ->test(UsersTable::class)
            ->set('selected', [(string) $admin->id, (string) $super->id])
            ->call('performDeleteSelected');

        // Locking yourself out mid-click, or removing the only account that can
        // hand roles back out, are both one-way doors.
        $this->assertTrue(User::whereKey($admin->id)->exists());
        $this->assertTrue(User::whereKey($super->id)->exists());
    }

    public function test_a_role_that_is_assigned_or_is_super_is_refused(): void
    {
        $admin = $this->admin();
        $admin->givePermissionTo('manage roles');

        $super = Role::findByName('super');
        $inUse = Role::findByName('admin');          // $admin holds it
        $free = Role::create(['name' => 'spare', 'guard_name' => 'web']);

        Livewire::actingAs($admin)
            ->test(RolesTable::class)
            ->set('selected', [(string) $super->id, (string) $inUse->id, (string) $free->id])
            ->call('performDeleteSelected');

        $this->assertTrue(Role::whereKey($super->id)->exists());
        $this->assertTrue(Role::whereKey($inUse->id)->exists());
        $this->assertFalse(Role::whereKey($free->id)->exists());
    }

    public function test_deleting_a_questionnaire_response_takes_its_upload_off_the_disk(): void
    {
        Storage::fake('local');

        $form = Questionnaire::create(['title' => 'Intake', 'is_published' => true]);
        $form->questions()->create([
            'key' => 'cv', 'type' => 'file', 'label' => 'CV', 'is_required' => true, 'is_active' => true,
        ]);

        $this->post(route('forms.store', $form), [
            'uploads' => ['cv' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf')],
        ]);

        $response = QuestionnaireResponse::sole();
        $path = $response->files()->sole()->path;

        Livewire::actingAs($this->admin())
            ->test(QuestionnaireResponsesTable::class, ['questionnaireId' => $form->id])
            ->call('performDelete', $response->id);

        $this->assertSame(0, QuestionnaireResponse::count());
        // The rows cascade; the file would otherwise be left behind for good.
        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_a_registration_keeps_the_student_account(): void
    {
        $admin = $this->admin();
        $admin->givePermissionTo('manage registrations');

        $course = Course::create(['title' => 'Data Analytics', 'description' => 'x']);
        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id, 'name' => 'Ama', 'email' => 'ama@example.com',
            'phone' => '+233240000000', 'amount' => '100.00', 'reference' => 'CRS-1',
            'status' => 'paid', 'completed_at' => now(),
        ]);
        app(StudentAccountService::class)->issueFor($enrollment);

        Livewire::actingAs($admin)
            ->test(CourseEnrollmentsTable::class)
            ->call('performDelete', $enrollment->id);

        $this->assertSame(0, CourseEnrollment::count());
        // The account is the person's login across every course they take, so
        // it outlives any one registration.
        $this->assertSame(1, User::where('email', 'ama@example.com')->count());
    }
}
