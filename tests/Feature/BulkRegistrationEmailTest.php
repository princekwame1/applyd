<?php

namespace Tests\Feature;

use App\Http\Controllers\Dashboard\BulkEmailController;
use App\Livewire\RegistrationsTable;
use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class BulkRegistrationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
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

    protected function withSelection(array $registrations): self
    {
        return $this->withSession([
            BulkEmailController::SESSION_KEY => collect($registrations)->pluck('id')->all(),
        ]);
    }

    public function test_compose_screen_lists_the_selected_recipients(): void
    {
        $a = $this->registration(['full_name' => 'Ama Mensah']);
        $b = $this->registration(['full_name' => 'Kofi Boateng']);

        $this->actingAs($this->admin())
            ->withSelection([$a, $b])
            ->get(route('dashboard.registrations.bulk-email'))
            ->assertOk()
            ->assertSee('Send email to 2 registrants')
            ->assertSee($a->email)
            ->assertSee($b->email);
    }

    public function test_compose_screen_redirects_when_nothing_is_selected(): void
    {
        $this->actingAs($this->admin())
            ->get(route('dashboard.registrations.bulk-email'))
            ->assertRedirect(route('dashboard.registrations'))
            ->assertSessionHas('error');
    }

    public function test_it_sends_a_personalised_copy_to_every_selected_registrant(): void
    {
        Mail::fake();

        $a = $this->registration(['full_name' => 'Ama Mensah', 'city' => 'Accra']);
        $b = $this->registration(['full_name' => 'Kofi Boateng', 'city' => 'Kumasi']);

        $this->actingAs($this->admin())
            ->withSelection([$a, $b])
            ->post(route('dashboard.registrations.bulk-email.send'), [
                'subject' => 'Session update for {{ first_name }}',
                'heading' => 'Timetable change',
                'body' => '<p>Hi {{ first_name }}, we are moving the {{ city }} session.</p>',
                'cta_label' => '',
                'cta_url' => '',
            ])
            ->assertRedirect(route('dashboard.registrations'))
            ->assertSessionHas('success');

        Mail::assertSent(TemplatedMail::class, 2);

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $m) => $m->hasTo($a->email)
            && $m->subjectLine === 'Session update for Ama'
            && str_contains($m->bodyHtml, 'moving the Accra session'));

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $m) => $m->hasTo($b->email)
            && $m->subjectLine === 'Session update for Kofi'
            && str_contains($m->bodyHtml, 'moving the Kumasi session'));

        $this->assertSame(2, EmailLog::count());
        $this->assertSame(['bulk_broadcast', 'bulk_broadcast'], EmailLog::pluck('template_key')->all());
        $this->assertSame([$a->id, $b->id], EmailLog::orderBy('registration_id')->pluck('registration_id')->all());
    }

    public function test_the_selection_is_cleared_after_sending(): void
    {
        Mail::fake();
        $a = $this->registration();

        $this->actingAs($this->admin())
            ->withSelection([$a])
            ->post(route('dashboard.registrations.bulk-email.send'), [
                'subject' => 'Hello',
                'body' => '<p>Hi</p>',
            ])
            ->assertSessionMissing(BulkEmailController::SESSION_KEY);
    }

    public function test_the_opt_in_filter_excludes_registrants_who_did_not_opt_in(): void
    {
        Mail::fake();

        $yes = $this->registration(['marketing_opt_in' => true]);
        $no = $this->registration(['marketing_opt_in' => false]);

        $this->actingAs($this->admin())
            ->withSelection([$yes, $no])
            ->post(route('dashboard.registrations.bulk-email.send'), [
                'subject' => 'Promo',
                'body' => '<p>Deal inside</p>',
                'opted_in_only' => 1,
            ])
            ->assertRedirect(route('dashboard.registrations'));

        Mail::assertSent(TemplatedMail::class, 1);
        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $m) => $m->hasTo($yes->email));
        Mail::assertNotSent(TemplatedMail::class, fn (TemplatedMail $m) => $m->hasTo($no->email));
    }

    public function test_script_tags_are_stripped_from_the_body(): void
    {
        Mail::fake();
        $a = $this->registration();

        $this->actingAs($this->admin())
            ->withSelection([$a])
            ->post(route('dashboard.registrations.bulk-email.send'), [
                'subject' => 'Hello',
                'body' => '<p>Hi</p><script>alert(1)</script>',
            ]);

        Mail::assertSent(TemplatedMail::class, fn (TemplatedMail $m) => ! str_contains($m->bodyHtml, '<script>'));
    }

    public function test_subject_and_body_are_required(): void
    {
        $a = $this->registration();

        $this->actingAs($this->admin())
            ->withSelection([$a])
            ->post(route('dashboard.registrations.bulk-email.send'), ['subject' => '', 'body' => ''])
            ->assertSessionHasErrors(['subject', 'body']);
    }

    public function test_a_button_needs_both_a_label_and_a_link(): void
    {
        $a = $this->registration();

        $this->actingAs($this->admin())
            ->withSelection([$a])
            ->post(route('dashboard.registrations.bulk-email.send'), [
                'subject' => 'Hello',
                'body' => '<p>Hi</p>',
                'cta_label' => 'Click me',
                'cta_url' => '',
            ])
            ->assertSessionHasErrors('cta_url');
    }

    public function test_the_table_bulk_action_stashes_the_ticked_rows_and_redirects_to_compose(): void
    {
        $a = $this->registration();
        $b = $this->registration();
        $ignored = $this->registration();

        $this->actingAs($this->admin());

        Livewire::test(RegistrationsTable::class)
            ->set('selected', [(string) $a->id, (string) $b->id])
            ->call('composeSelected')
            ->assertRedirect(route('dashboard.registrations.bulk-email'));

        $this->assertSame([$a->id, $b->id], session(BulkEmailController::SESSION_KEY));
        $this->assertNotContains($ignored->id, session(BulkEmailController::SESSION_KEY));
    }

    public function test_the_table_bulk_action_does_nothing_when_no_rows_are_ticked(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(RegistrationsTable::class)
            ->call('composeSelected')
            ->assertNoRedirect();

        $this->assertNull(session(BulkEmailController::SESSION_KEY));
    }

    public function test_guests_cannot_reach_the_bulk_email_screens(): void
    {
        $this->get(route('dashboard.registrations.bulk-email'))->assertRedirect(route('login'));
        $this->post(route('dashboard.registrations.bulk-email.send'))->assertRedirect(route('login'));
    }
}
