<?php

namespace Tests\Feature;

use App\Jobs\DeliverEmail;
use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Models\Registration;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Support\MailThrottle;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MailQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        MailThrottle::reset();
    }

    /** Pretend a real queue connection is configured (the suite runs on sync). */
    protected function withQueue(): void
    {
        config(['queue.default' => 'database']);
    }

    protected function registration(array $overrides = []): Registration
    {
        static $n = 0;
        $n++;

        return Registration::create(array_merge([
            'full_name' => 'Ama Mensah',
            'gender' => config('bootcamp.genders')[0],
            'age_range' => config('bootcamp.age_ranges')[0],
            'country' => 'Ghana',
            'city' => 'Accra',
            'phone_country_code' => '+233',
            'phone' => '24123456'.$n,
            'email' => 'person'.$n.'@example.com',
            'education' => config('bootcamp.education_levels')[0],
            'tools' => ['Notion'],
            'marketing_opt_in' => true,
        ], $overrides));
    }

    protected function log(array $overrides = []): EmailLog
    {
        return EmailLog::create(array_merge([
            'email' => 'ama@example.com',
            'subject' => 'Hello',
            'body' => '<p>Hello</p>',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_sending_queues_a_job_instead_of_delivering_inline(): void
    {
        $this->withQueue();
        Queue::fake();
        Mail::fake();

        app(EmailNotificationService::class)->send('ama@example.com', [
            'subject' => 'Hello',
            'body' => '<p>Hi</p>',
        ]);

        Mail::assertNothingSent();
        Queue::assertPushed(DeliverEmail::class);

        $log = EmailLog::first();
        $this->assertSame('queued', $log->status);
        $this->assertNull($log->sent_at);
    }

    public function test_a_broadcast_rides_the_bulk_queue_and_transactional_mail_does_not(): void
    {
        $this->withQueue();
        Queue::fake();

        $emails = app(EmailNotificationService::class);
        $payload = ['subject' => 'Hello', 'body' => '<p>Hi</p>'];

        $emails->send('bulk@example.com', $payload, null, null, 'bulk_broadcast', EmailNotificationService::BULK);
        $emails->send('one@example.com', $payload);

        Queue::assertPushedOn(config('mail.queues.bulk'), DeliverEmail::class);
        Queue::assertPushedOn(config('mail.queues.priority'), DeliverEmail::class);
    }

    public function test_without_a_queue_connection_mail_still_goes_out_inline(): void
    {
        // The suite runs on QUEUE_CONNECTION=sync — a site with no worker must
        // keep delivering rather than silently filling a table.
        Mail::fake();

        app(EmailNotificationService::class)->send('ama@example.com', [
            'subject' => 'Hello',
            'body' => '<p>Hi</p>',
        ]);

        Mail::assertSent(TemplatedMail::class);
        $this->assertSame('sent', EmailLog::first()->status);
    }

    public function test_the_job_delivers_the_logged_email(): void
    {
        Mail::fake();
        $log = $this->log(['status' => 'queued']);

        (new DeliverEmail($log->id))->handle(app(EmailNotificationService::class));

        Mail::assertSent(TemplatedMail::class, fn ($mail) => $mail->hasTo('ama@example.com'));
        $this->assertSame('sent', $log->fresh()->status);
    }

    public function test_the_job_does_not_send_an_email_that_already_went_out(): void
    {
        Mail::fake();
        $log = $this->log(['status' => 'sent', 'sent_at' => now()]);

        (new DeliverEmail($log->id))->handle(app(EmailNotificationService::class));

        Mail::assertNothingSent();
    }

    public function test_the_job_survives_a_log_row_that_has_been_deleted(): void
    {
        Mail::fake();

        (new DeliverEmail(999999))->handle(app(EmailNotificationService::class));

        Mail::assertNothingSent();
    }

    public function test_the_throttle_stops_the_hourly_allowance_being_exceeded(): void
    {
        config(['mail.hourly_limit' => 3]);

        $this->assertTrue(MailThrottle::attempt());
        $this->assertTrue(MailThrottle::attempt());
        $this->assertTrue(MailThrottle::attempt());
        $this->assertFalse(MailThrottle::attempt());

        $this->assertSame(0, MailThrottle::remaining());
        $this->assertGreaterThan(0, MailThrottle::availableIn());
    }

    public function test_the_window_rolls_so_an_old_send_frees_a_slot(): void
    {
        config(['mail.hourly_limit' => 2]);

        MailThrottle::attempt();
        MailThrottle::attempt();
        $this->assertFalse(MailThrottle::attempt());

        // An hour and a minute later the first two are outside the window.
        $this->travel(61)->minutes();

        $this->assertTrue(MailThrottle::attempt());
        $this->assertSame(1, MailThrottle::remaining());
    }

    public function test_a_throttled_job_is_released_instead_of_sent(): void
    {
        config(['mail.hourly_limit' => 1]);
        Mail::fake();

        MailThrottle::attempt(); // spend the hour's only slot

        $log = $this->log(['status' => 'queued']);
        $job = new DeliverEmail($log->id);

        $released = null;
        $queueJob = \Mockery::mock(Job::class);
        $queueJob->shouldReceive('release')->once()->andReturnUsing(function ($delay) use (&$released) {
            $released = $delay;
        });
        $job->setJob($queueJob);

        $job->handle(app(EmailNotificationService::class));

        Mail::assertNothingSent();
        $this->assertGreaterThan(0, $released);
        $this->assertSame('queued', $log->fresh()->status);
    }

    public function test_a_zero_limit_disables_the_throttle(): void
    {
        config(['mail.hourly_limit' => 0]);

        foreach (range(1, 50) as $i) {
            $this->assertTrue(MailThrottle::attempt());
        }

        $this->assertSame(0, MailThrottle::availableIn());
    }

    public function test_a_failed_job_marks_the_log_failed_so_it_can_be_resent(): void
    {
        $log = $this->log(['status' => 'queued']);

        (new DeliverEmail($log->id))->failed(new \RuntimeException('550 mailbox unavailable'));

        $log->refresh();
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('550', $log->response);
    }

    public function test_a_resend_re_queues_the_row_and_counts_the_retry(): void
    {
        $this->withQueue();
        Queue::fake();

        $log = $this->log(['status' => 'failed', 'response' => 'timed out']);

        app(EmailNotificationService::class)->resend($log);

        Queue::assertPushed(DeliverEmail::class);

        $log->refresh();
        $this->assertSame('queued', $log->status);
        $this->assertSame(1, $log->retry_count);
        $this->assertNull($log->response);
    }

    public function test_the_delivery_screen_reports_what_is_waiting(): void
    {
        $this->withQueue();
        config(['mail.hourly_limit' => 100]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->log(['status' => 'queued']);
        $this->log(['status' => 'queued']);

        $this->actingAs($admin)
            ->get(route('dashboard.email-logs'))
            ->assertOk()
            ->assertSee('<strong>2</strong> waiting to go out', false)
            ->assertSee('sent this hour');
    }

    public function test_a_bulk_broadcast_is_queued_and_says_so(): void
    {
        $this->withQueue();
        Queue::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $people = collect(range(1, 3))->map(fn () => $this->registration());

        $this->actingAs($admin)
            ->withSession(['bulk_email.registration_ids' => $people->pluck('id')->all()])
            ->post(route('dashboard.registrations.bulk-email.send'), [
                'subject' => 'Hello {{ first_name }}',
                'body' => '<p>Hi {{ first_name }}</p>',
            ])
            ->assertRedirect(route('dashboard.registrations'))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'queued for delivery'));

        Queue::assertPushed(DeliverEmail::class, 3);
        $this->assertSame(3, EmailLog::where('status', 'queued')->count());
    }
}
