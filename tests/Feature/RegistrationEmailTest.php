<?php

namespace Tests\Feature;

use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Ama Mensah',
            'gender' => config('bootcamp.genders')[0],
            'age_range' => config('bootcamp.age_ranges')[0],
            'country' => 'Ghana',
            'city' => 'Accra',
            'phone' => '+233241234567',
            'email' => 'ama@example.com',
            'education' => config('bootcamp.education_levels')[0],
            'tools' => ['Notion'],
            'marketing_opt_in' => 1,
        ], $overrides);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_registering_sends_and_logs_a_confirmation_email(): void
    {
        Mail::fake();

        $this->post('/register', $this->registrationPayload())
            ->assertRedirect(route('register.thanks'));

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            return $mail->hasTo('ama@example.com')
                && str_contains($mail->subjectLine, 'Ama')
                && str_contains($mail->bodyHtml, 'Ama Mensah')
                && ! str_contains($mail->bodyHtml, '{{');
        });

        $log = EmailLog::first();
        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
        $this->assertSame('registration_confirmation', $log->template_key);
        $this->assertSame(Registration::first()->id, $log->registration_id);
    }

    public function test_a_disabled_template_is_not_sent(): void
    {
        Mail::fake();
        EmailTemplate::create(['key' => 'registration_confirmation', 'enabled' => false]);

        $this->post('/register', $this->registrationPayload());

        Mail::assertNothingSent();
        $this->assertSame(0, EmailLog::count());
        $this->assertSame(1, Registration::count());
    }

    public function test_admin_can_resend_the_confirmation_email_for_a_registration(): void
    {
        Mail::fake();
        $registration = Registration::create($this->registrationPayload([
            'phone_country_code' => '+233',
            'phone' => '241234567',
        ]));

        $this->actingAs($this->admin())
            ->post(route('dashboard.registrations.resend-email', $registration))
            ->assertRedirect();

        Mail::assertSent(TemplatedMail::class, 1);
        $this->assertSame('sent', EmailLog::first()->status);
    }

    public function test_admin_can_resend_a_logged_email(): void
    {
        Mail::fake();
        $log = EmailLog::create([
            'email' => 'ama@example.com',
            'subject' => 'Hello',
            'body' => '<p>Hi</p>',
            'status' => 'failed',
        ]);

        $this->actingAs($this->admin())
            ->post(route('dashboard.email-logs.resend', $log))
            ->assertRedirect();

        Mail::assertSent(TemplatedMail::class, 1);
        $log->refresh();
        $this->assertSame('sent', $log->status);
        $this->assertSame(1, $log->retry_count);
    }

    public function test_admin_can_customise_a_template_and_the_override_is_used(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('dashboard.email-templates'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.email-templates.edit', 'registration_confirmation'))->assertOk();

        $this->actingAs($admin)->put(route('dashboard.email-templates.update', 'registration_confirmation'), [
            'subject' => 'Welcome aboard {{ first_name }}',
            'heading' => 'You are in',
            'body' => '<p>Hello {{ full_name }} from {{ city }}.</p><script>alert(1)</script>',
            'cta_label' => '',
            'cta_url' => '',
            'enabled' => 1,
        ])->assertRedirect();

        $template = EmailTemplate::resolve('registration_confirmation');
        $this->assertStringNotContainsString('<script>', $template['body']);
        $this->assertTrue($template['customised']);

        $this->post('/register', $this->registrationPayload());

        Mail::assertSent(TemplatedMail::class, function (TemplatedMail $mail) {
            return $mail->subjectLine === 'Welcome aboard Ama'
                && str_contains($mail->bodyHtml, 'Hello Ama Mensah from Accra.');
        });
    }

    public function test_template_can_be_reset_to_the_default_copy(): void
    {
        EmailTemplate::create(['key' => 'registration_confirmation', 'subject' => 'Custom']);

        $this->actingAs($this->admin())
            ->delete(route('dashboard.email-templates.reset', 'registration_confirmation'))
            ->assertRedirect();

        $this->assertSame(0, EmailTemplate::count());
        $this->assertFalse(EmailTemplate::resolve('registration_confirmation')['customised']);
    }

    public function test_unknown_template_keys_404(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard.email-templates.edit', 'nope'))
            ->assertNotFound();
    }

    public function test_email_logs_page_and_preview_render(): void
    {
        $admin = $this->admin();
        $log = EmailLog::create([
            'email' => 'ama@example.com',
            'subject' => 'Hello',
            'body' => '<p>Hi</p>',
            'heading' => 'Hi there',
            'status' => 'sent',
        ]);

        $this->actingAs($admin)->get(route('dashboard.email-logs'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard.email-logs.show', $log))->assertOk()->assertSee('Hi there');
        $this->actingAs($admin)->get(route('dashboard.email-templates.preview', 'registration_confirmation'))
            ->assertOk()
            ->assertDontSee('{{');
    }

    public function test_registration_screens_render_the_resend_controls(): void
    {
        $registration = Registration::create($this->registrationPayload([
            'phone_country_code' => '+233',
            'phone' => '241234567',
        ]));

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('dashboard.registrations'))
            ->assertOk()
            ->assertSee('Resend email');

        $this->actingAs($admin)->get(route('dashboard.show', $registration))
            ->assertOk()
            ->assertSee('Resend confirmation email');
    }

    public function test_guests_cannot_reach_the_email_screens(): void
    {
        $this->get(route('dashboard.email-templates'))->assertRedirect(route('login'));
        $this->get(route('dashboard.email-logs'))->assertRedirect(route('login'));
    }
}
