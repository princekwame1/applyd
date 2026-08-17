<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\StudentAccountService;
use App\Support\Portal;
use App\Support\StudentIds;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Issuing a student ID and a learning-portal login off the back of a completed
 * course registration.
 */
class StudentAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function service(): StudentAccountService
    {
        return app(StudentAccountService::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function course(array $attributes = []): Course
    {
        return Course::create(array_merge([
            'title' => 'Data Analytics',
            'description' => 'Learn data analytics.',
        ], $attributes));
    }

    protected function enrollment(array $attributes = []): CourseEnrollment
    {
        return CourseEnrollment::create(array_merge([
            'course_id' => $this->course()->id,
            'name' => 'Ama Mensah',
            'email' => 'ama.mensah@example.com',
            'phone' => '+233241234567',
            'amount' => '100.00',
            'reference' => 'CRS-TEST-'.uniqid(),
            'status' => 'paid',
            'completed_at' => now(),
        ], $attributes));
    }

    public function test_a_completed_registration_gets_a_student_id_and_an_account(): void
    {
        $enrollment = $this->enrollment();

        $result = $this->service()->issueFor($enrollment);

        $user = $result['user'];
        $enrollment->refresh();

        $this->assertSame(now()->format('Y').'0001', $result['student_id']);
        $this->assertSame($result['student_id'], $enrollment->student_id);
        $this->assertSame($result['student_id'], $user->student_id);
        $this->assertSame($user->id, $enrollment->user_id);
        $this->assertSame('ama.mensah@example.com', $user->email);
        $this->assertTrue($result['created']);
    }

    public function test_the_new_account_carries_the_student_role_the_portal_requires(): void
    {
        $result = $this->service()->issueFor($this->enrollment());

        // The portal turns away any account without student or lecturer.
        $this->assertTrue($result['user']->hasRole('student'));
    }

    public function test_the_temporary_password_actually_signs_them_in(): void
    {
        $result = $this->service()->issueFor($this->enrollment());

        $this->assertNotNull($result['password']);
        $this->assertTrue(Hash::check($result['password'], $result['user']->password));
        $this->assertTrue($result['user']->must_change_password);
    }

    public function test_every_student_id_is_exactly_eight_digits(): void
    {
        $result = $this->service()->issueFor($this->enrollment());

        $this->assertMatchesRegularExpression('/^\d{8}$/', $result['student_id']);
        $this->assertTrue(StudentIds::looksValid($result['student_id']));
        // Nothing to trip up a numeric keypad or a phone call.
        $this->assertSame(8, strlen($result['student_id']));
    }

    public function test_student_ids_run_in_sequence_within_a_year(): void
    {
        foreach (['one@example.com', 'two@example.com', 'three@example.com'] as $i => $email) {
            $result = $this->service()->issueFor($this->enrollment(['email' => $email]));

            $this->assertSame(now()->format('Y').str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), $result['student_id']);
        }
    }

    public function test_the_sequence_restarts_each_year(): void
    {
        User::factory()->create(['student_id' => '20250042']);

        // Last year's numbering is left where it is rather than carried over.
        $this->assertSame(now()->format('Y').'0001', StudentIds::next());
        $this->assertSame('20250043', StudentIds::next(2025));
    }

    public function test_the_year_prefix_keeps_intakes_from_colliding(): void
    {
        // A four-digit year in front means 2025's numbers can never be reached
        // by 2026's sequence, however many students either year takes.
        User::factory()->create(['student_id' => '20259999']);

        $this->assertSame(now()->format('Y').'0001', StudentIds::next());
    }

    public function test_issuing_twice_never_mints_a_second_account_or_password(): void
    {
        $enrollment = $this->enrollment();

        $first = $this->service()->issueFor($enrollment);
        $second = $this->service()->issueFor($enrollment->fresh());

        $this->assertSame($first['user']->id, $second['user']->id);
        $this->assertSame($first['student_id'], $second['student_id']);
        // A repeat call can't hand back a password: the stored one is a hash.
        $this->assertNull($second['password']);
        $this->assertFalse($second['created']);
        $this->assertSame(1, User::where('email', $enrollment->email)->count());
    }

    public function test_a_second_course_reuses_the_person_their_id_and_their_password(): void
    {
        $first = $this->service()->issueFor($this->enrollment());
        $originalHash = $first['user']->password;

        $second = $this->service()->issueFor($this->enrollment([
            'email' => 'AMA.MENSAH@Example.com',      // same person, typed differently
            'reference' => 'CRS-SECOND',
        ]));

        $this->assertSame($first['user']->id, $second['user']->id);
        $this->assertSame($first['student_id'], $second['student_id']);
        $this->assertNull($second['password']);
        // Registering again must never reset a login that already works.
        $this->assertSame($originalHash, $second['user']->fresh()->password);
        $this->assertSame(1, User::whereRaw('LOWER(email) = ?', ['ama.mensah@example.com'])->count());
    }

    public function test_an_existing_account_keeps_its_password_and_simply_gains_the_role(): void
    {
        $existing = User::factory()->create([
            'email' => 'ama.mensah@example.com',
            'password' => 'their-own-password',
        ]);
        $hash = $existing->password;

        $result = $this->service()->issueFor($this->enrollment());

        $this->assertSame($existing->id, $result['user']->id);
        $this->assertNull($result['password']);
        $this->assertSame($hash, $result['user']->fresh()->password);
        $this->assertTrue($result['user']->hasRole('student'));
        $this->assertFalse((bool) $result['user']->fresh()->must_change_password);
    }

    public function test_the_credentials_email_carries_the_id_password_and_portal_link(): void
    {
        $enrollment = $this->enrollment();

        $result = $this->service()->issueAndNotify($enrollment);

        $log = EmailLog::where('template_key', 'student_credentials')->sole();

        $this->assertSame($enrollment->email, $log->email);
        $this->assertStringContainsString($result['student_id'], $log->body);
        $this->assertStringContainsString($result['password'], $log->body);
        $this->assertStringContainsString('Data Analytics', $log->body);
        // Sign-in is on the portal, not this site.
        $this->assertSame(Portal::loginUrl(), $log->cta_url);
        $this->assertNotNull($enrollment->fresh()->credentials_sent_at);
    }

    /**
     * One character outside GSM-7 flips an SMS to UCS-2, where a segment is 70
     * characters instead of 160 — so a stray em dash triples the bill for every
     * student. Pinned here because it already happened once.
     */
    public function test_the_sms_fits_one_segment_and_stays_in_the_gsm_alphabet(): void
    {
        config(['services.portal.url' => 'https://sts.applydacademy.com']);

        $service = $this->service();
        $method = new \ReflectionMethod($service, 'smsMessage');
        $method->setAccessible(true);

        $enrollment = $this->enrollment();
        $result = $service->issueFor($enrollment);

        foreach ([$result['password'], null] as $password) {
            $sms = $method->invoke($service, $enrollment->fresh('course'), $password);

            $this->assertLessThanOrEqual(160, strlen($sms), 'SMS spills into a second segment: '.$sms);
            // Plain ASCII is comfortably inside GSM-7; anything above it isn't.
            $this->assertSame($sms, mb_convert_encoding($sms, 'ASCII', 'UTF-8'), 'SMS carries a non-GSM character: '.$sms);
            $this->assertStringContainsString('https://sts.applydacademy.com/login', $sms);
            $this->assertStringContainsString($result['student_id'], $sms);
        }
    }

    public function test_the_portal_link_follows_the_configured_portal_url(): void
    {
        config(['services.portal.url' => 'https://portal.example.com/']);

        $this->assertSame('https://portal.example.com/login', Portal::loginUrl());

        // With no portal configured we fall back rather than emailing a broken
        // link — a wrong door beats no door.
        config(['services.portal.url' => null]);

        $this->assertSame(route('login'), Portal::loginUrl());
    }

    public function test_resending_issues_a_fresh_password_while_the_old_one_is_untouched(): void
    {
        $enrollment = $this->enrollment();
        $first = $this->service()->issueAndNotify($enrollment);

        $result = $this->service()->resendCredentials($enrollment->fresh());

        $this->assertTrue($result['reset']);
        $this->assertSame($first['student_id'], $result['student_id']);

        // The superseded password stops working; the new one is what was sent.
        $user = $first['user']->fresh();
        $this->assertFalse(Hash::check($first['password'], $user->password));
        $this->assertTrue($user->must_change_password);

        // Two sends now: the original and the resend.
        $this->assertSame(2, EmailLog::where('template_key', 'student_credentials')->count());

        $latest = EmailLog::where('template_key', 'student_credentials')->latest('id')->first();
        $this->assertStringContainsString($result['student_id'], $latest->body);
        $this->assertStringNotContainsString($first['password'], $latest->body);
    }

    public function test_resending_leaves_a_password_the_student_chose_alone(): void
    {
        $enrollment = $this->enrollment();
        $this->service()->issueFor($enrollment);

        // They signed in and set their own — which is what clearing the flag means.
        $user = $enrollment->fresh()->user;
        $user->forceFill(['password' => 'chosen-by-them', 'must_change_password' => false])->save();
        $hash = $user->fresh()->password;

        $result = $this->service()->resendCredentials($enrollment->fresh());

        $this->assertFalse($result['reset']);
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_an_admin_can_issue_and_resend_from_the_dashboard(): void
    {
        $enrollment = $this->enrollment();

        $this->actingAs($this->admin())
            ->post(route('dashboard.course-registrations.credentials', $enrollment))
            ->assertRedirect();

        $this->assertNotNull($enrollment->fresh()->student_id);
        $this->assertNotNull($enrollment->fresh()->user_id);
    }

    public function test_nothing_is_issued_for_a_registration_that_is_not_finished(): void
    {
        $enrollment = $this->enrollment(['completed_at' => null]);

        $this->actingAs($this->admin())
            ->post(route('dashboard.course-registrations.credentials', $enrollment))
            ->assertSessionHas('error');

        $this->assertNull($enrollment->fresh()->student_id);
        $this->assertSame(0, User::where('email', $enrollment->email)->count());
    }

    public function test_a_guest_cannot_issue_credentials(): void
    {
        $enrollment = $this->enrollment();

        $this->post(route('dashboard.course-registrations.credentials', $enrollment))
            ->assertRedirect(route('login'));

        $this->assertNull($enrollment->fresh()->student_id);
    }

    public function test_a_delivery_failure_never_undoes_the_issued_account(): void
    {
        // A mailer with nowhere to send: the service records the failure and
        // carries on, because a paid registration must not hinge on SMTP.
        config(['mail.from.address' => null]);

        $enrollment = $this->enrollment();
        $result = $this->service()->issueAndNotify($enrollment);

        $this->assertNotNull($result['student_id']);
        $this->assertSame('failed', EmailLog::sole()->status);
        $this->assertNotNull($enrollment->fresh()->user_id);
    }
}
