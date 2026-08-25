<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\PaymentReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super', 'web');
        config(['services.mnotify.api_key' => 'test-key', 'services.mnotify.sender_id' => 'Applyd']);
        Http::fake(['api.mnotify.com/*' => Http::response(['status' => 'success'], 200)]);
    }

    protected function course(float $tuition = 1200, float $formFee = 100): Course
    {
        return Course::create([
            'title' => 'Digital Marketing',
            'description' => 'A course.',
            'price' => $tuition,
            'form_price' => $formFee,
        ]);
    }

    protected function enrollment(array $attrs = []): CourseEnrollment
    {
        return CourseEnrollment::create(array_merge([
            'course_id' => $this->course()->id,
            'name' => 'Ama Serwaa Boateng',
            'email' => 'ama@example.com',
            'phone' => '+233240835458',
            'amount' => 100,
            'reference' => 'CRS-1-'.strtoupper(fake()->bothify('??????????')),
            'status' => 'pending',
        ], $attrs));
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super');

        return $admin;
    }

    // ---- the message ----------------------------------------------------

    public function test_both_reminders_fit_one_gsm7_segment(): void
    {
        // A single non-GSM-7 character halves the segment to 70 characters, so
        // the alphabet matters as much as the length.
        $enrollment = $this->enrollment([
            'name' => 'Nana Yaa Konadu Asantewaa-Mensah',
            'status' => 'paid',
            'tuition_amount' => 200,
            'tuition_status' => 'partial',
        ]);

        // Pinned against a longer host than production's, so the headroom is
        // real rather than a coincidence of today's APP_URL.
        config(['app.url' => 'https://www.applydacademy.com.gh']);
        url()->forceRootUrl(config('app.url'));

        $service = app(PaymentReminderService::class);

        foreach ([
            $service->formFeeMessage($enrollment),
            $service->tuitionMessage($enrollment->fresh('course')),
        ] as $message) {
            $this->assertLessThanOrEqual(
                PaymentReminderService::SEGMENT,
                strlen($message),
                'Message is longer than one SMS segment: '.$message
            );
            $this->assertSame($message, mb_convert_encoding($message, 'ASCII', 'UTF-8'), 'Message left the GSM-7 alphabet.');
        }
    }

    public function test_the_message_carries_the_students_own_payment_link(): void
    {
        $enrollment = $this->enrollment();

        $message = app(PaymentReminderService::class)->formFeeMessage($enrollment);

        $this->assertStringContainsString($enrollment->fresh()->payUrl(), $message);
        $this->assertStringContainsString('GHS 100', $message);
        $this->assertStringContainsString('Ama', $message);
    }

    // ---- who gets one ---------------------------------------------------

    public function test_a_student_who_already_paid_the_form_fee_is_never_reminded(): void
    {
        $enrollment = $this->enrollment(['status' => 'paid']);

        $this->assertFalse(app(PaymentReminderService::class)->sendFormFeeReminder($enrollment));
        $this->assertSame(0, SmsLog::count());
        $this->assertNull($enrollment->fresh()->form_reminder_sent_at);
    }

    public function test_a_failed_payment_still_owes_the_form_fee(): void
    {
        $enrollment = $this->enrollment(['status' => 'failed']);

        $this->assertTrue(app(PaymentReminderService::class)->sendFormFeeReminder($enrollment));
        $this->assertSame(1, SmsLog::count());
        $this->assertNotNull($enrollment->fresh()->form_reminder_sent_at);
    }

    public function test_tuition_is_not_chased_while_the_form_fee_is_unpaid(): void
    {
        // They get the form reminder instead; two chasers at once is just noise.
        $enrollment = $this->enrollment(['status' => 'pending']);

        $this->assertFalse($enrollment->owesTuition());
        $this->assertFalse(app(PaymentReminderService::class)->sendTuitionReminder($enrollment));
        $this->assertSame(0, SmsLog::count());
    }

    public function test_a_part_payment_still_owes_the_balance(): void
    {
        $enrollment = $this->enrollment([
            'status' => 'paid',
            'tuition_amount' => 600,
            'tuition_status' => 'partial',
        ])->fresh('course');

        $this->assertTrue($enrollment->owesTuition());
        $this->assertEqualsWithDelta(600.0, $enrollment->tuitionBalance(), 0.001);

        $message = app(PaymentReminderService::class)->tuitionMessage($enrollment);
        $this->assertStringContainsString('GHS 600', $message);
    }

    public function test_a_free_course_never_owes_tuition(): void
    {
        $free = Course::create(['title' => 'Free Intro', 'description' => 'x', 'price' => 0, 'form_price' => 50]);
        $enrollment = $this->enrollment(['course_id' => $free->id, 'status' => 'paid'])->fresh('course');

        $this->assertFalse($enrollment->owesTuition());
    }

    // ---- the per-row buttons --------------------------------------------

    public function test_admin_can_text_a_form_fee_reminder_from_the_row(): void
    {
        $enrollment = $this->enrollment();

        $this->actingAs($this->admin())
            ->from(route('dashboard.course-registrations'))
            ->post(route('dashboard.course-registrations.remind-form', $enrollment))
            ->assertRedirect(route('dashboard.course-registrations'))
            ->assertSessionHas('status');

        $this->assertSame(1, SmsLog::count());
        $this->assertNotNull($enrollment->fresh()->form_reminder_sent_at);
    }

    public function test_reminding_someone_who_already_paid_reports_it_and_sends_nothing(): void
    {
        $enrollment = $this->enrollment(['status' => 'paid']);

        $this->actingAs($this->admin())
            ->from(route('dashboard.course-registrations'))
            ->post(route('dashboard.course-registrations.remind-form', $enrollment))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, SmsLog::count());
    }

    public function test_a_guest_cannot_send_reminders(): void
    {
        $enrollment = $this->enrollment();

        $this->post(route('dashboard.course-registrations.remind-form', $enrollment))->assertRedirect();
        $this->assertSame(0, SmsLog::count());
    }

    // ---- the payment link -----------------------------------------------

    public function test_the_pay_link_signs_a_settled_applicant_into_their_application(): void
    {
        $enrollment = $this->enrollment(['status' => 'paid']);

        $this->get(route('enroll.pay', $enrollment->payToken()))
            ->assertRedirect(route('application.complete'));

        $this->assertSame($enrollment->id, session('applicant_id'));
    }

    public function test_an_unknown_pay_token_is_a_404(): void
    {
        $this->get(route('enroll.pay', 'nosuchtokenatall'))->assertNotFound();
    }

    public function test_a_token_is_minted_once_and_then_kept(): void
    {
        // The link is texted out, so it has to keep resolving.
        $enrollment = $this->enrollment();

        $first = $enrollment->payToken();
        $this->assertSame($first, $enrollment->fresh()->payToken());
    }
}
