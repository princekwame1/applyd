<?php

namespace Tests\Feature;

use App\Livewire\CourseEnrollmentsTable;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\StudentAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sending a student their portal login again from the dashboard — one row at a
 * time, or a whole batch.
 */
class ResendStudentCredentialsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function enrollment(array $attributes = []): CourseEnrollment
    {
        $course = Course::firstOrCreate(['title' => 'Data Analytics'], ['description' => 'x']);

        return CourseEnrollment::create(array_merge([
            'course_id' => $course->id,
            'name' => 'Ama Mensah',
            'email' => 'ama'.uniqid().'@example.com',
            'phone' => '+233240000000',
            'amount' => '100.00',
            'reference' => 'CRS-'.uniqid(),
            'status' => 'paid',
            'completed_at' => now(),
        ], $attributes));
    }

    public function test_the_page_explains_how_to_resend_and_shows_the_sign_in_link(): void
    {
        config(['services.portal.url' => 'https://portal.example.com']);

        $this->actingAs($this->admin())
            ->get(route('dashboard.course-registrations'))
            ->assertOk()
            ->assertSee('Student logins')
            ->assertSee('Send login details to selected')
            ->assertSee('https://portal.example.com/login');
    }

    public function test_the_page_warns_when_no_portal_url_is_configured(): void
    {
        config(['services.portal.url' => null]);

        $this->actingAs($this->admin())
            ->get(route('dashboard.course-registrations'))
            ->assertOk()
            // Sending a link a student can't sign in with is worse than not
            // sending, so the screen says so before anyone presses the button.
            ->assertSee("PORTAL_URL isn't set", false);
    }

    public function test_one_student_can_be_sent_their_details_again(): void
    {
        $enrollment = $this->enrollment();
        $first = app(StudentAccountService::class)->issueAndNotify($enrollment);

        $this->actingAs($this->admin())
            ->post(route('dashboard.course-registrations.credentials', $enrollment))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(2, EmailLog::where('template_key', 'student_credentials')->count());

        // A resend can only ever be a new password — the stored one is a hash.
        $user = $first['user']->fresh();
        $this->assertFalse(Hash::check($first['password'], $user->password));
        $this->assertSame($first['student_id'], $user->student_id);
    }

    public function test_a_batch_can_be_sent_in_one_go(): void
    {
        $one = $this->enrollment();
        $two = $this->enrollment();

        Livewire::actingAs($this->admin())
            ->test(CourseEnrollmentsTable::class)
            ->set('selected', [(string) $one->id, (string) $two->id])
            ->call('performSendCredentialsSelected');

        $this->assertSame(2, EmailLog::where('template_key', 'student_credentials')->count());
        $this->assertNotNull($one->fresh()->student_id);
        $this->assertNotNull($two->fresh()->student_id);
        $this->assertNotNull($one->fresh()->credentials_sent_at);
    }

    public function test_a_half_finished_registration_is_skipped_not_sent(): void
    {
        $done = $this->enrollment();
        $unfinished = $this->enrollment(['completed_at' => null]);

        Livewire::actingAs($this->admin())
            ->test(CourseEnrollmentsTable::class)
            ->set('selected', [(string) $done->id, (string) $unfinished->id])
            ->call('performSendCredentialsSelected');

        $this->assertSame(1, EmailLog::where('template_key', 'student_credentials')->count());
        $this->assertNotNull($done->fresh()->student_id);
        // No ID for someone who hasn't finished — it would say "enrolled".
        $this->assertNull($unfinished->fresh()->student_id);
        $this->assertSame(0, User::where('email', $unfinished->email)->count());
    }

    public function test_a_student_who_chose_their_own_password_keeps_it(): void
    {
        $enrollment = $this->enrollment();
        app(StudentAccountService::class)->issueFor($enrollment);

        $user = $enrollment->fresh()->user;
        $user->forceFill(['password' => 'chosen-by-them', 'must_change_password' => false])->save();
        $hash = $user->fresh()->password;

        Livewire::actingAs($this->admin())
            ->test(CourseEnrollmentsTable::class)
            ->set('selected', [(string) $enrollment->id])
            ->call('performSendCredentialsSelected');

        // They still get the reminder, but their working password is untouched.
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame(1, EmailLog::where('template_key', 'student_credentials')->count());
    }

    public function test_the_batch_send_is_refused_for_anyone_who_is_not_an_admin(): void
    {
        $enrollment = $this->enrollment();
        $outsider = User::factory()->create();
        $outsider->assignRole('student');

        // Livewire methods are callable over HTTP by whoever can reach them, so
        // the check has to live in the component, not just on the page.
        Livewire::actingAs($outsider)
            ->test(CourseEnrollmentsTable::class)
            ->set('selected', [(string) $enrollment->id])
            ->call('performSendCredentialsSelected')
            ->assertForbidden();

        $this->assertNull($enrollment->fresh()->student_id);
        $this->assertSame(0, EmailLog::count());
    }

    public function test_the_table_can_pick_out_who_is_still_waiting(): void
    {
        $waiting = $this->enrollment(['name' => 'Waiting Student']);
        $alreadySent = $this->enrollment(['name' => 'Sent Student']);

        app(StudentAccountService::class)->issueAndNotify($alreadySent);

        Livewire::actingAs($this->admin())
            ->test(CourseEnrollmentsTable::class)
            ->call('setFilter', 'login_details', 'ready')
            ->assertSee('Waiting Student')
            ->assertDontSee('Sent Student');
    }

    public function test_the_row_shows_the_student_id_once_it_has_been_issued(): void
    {
        $enrollment = $this->enrollment();
        $result = app(StudentAccountService::class)->issueAndNotify($enrollment);

        Livewire::actingAs($this->admin())
            ->test(CourseEnrollmentsTable::class)
            ->assertSee($result['student_id']);
    }
}
