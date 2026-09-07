<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Forgotten passwords on the main site.
 *
 * The token table is shared with the learning portal, so a reset issued on
 * either site works on the same account — the two differ only in where you
 * sign in afterwards.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_forgot_password_page_renders(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Forgot your password?');
    }

    public function test_a_reset_link_is_sent_to_an_account(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'ama@example.com']);

        $this->post('/forgot-password', ['email' => 'ama@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    /**
     * An unknown address gets the same answer as a known one. A message that
     * appears only for real accounts answers "does this person bank here?"
     * just as plainly as being asked would.
     */
    public function test_an_unknown_address_is_answered_the_same_way(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', 'If that address has an account, a reset link is on its way.');

        Notification::assertNothingSent();
    }

    /** A broken mailer must not 500 a visitor, and must not leak who exists. */
    public function test_a_broken_mailer_is_not_a_500(): void
    {
        $user = User::factory()->create(['email' => 'ama@example.com']);

        Password::shouldReceive('sendResetLink')->once()->andThrow(new \Error(
            'Class "Symfony\\Component\\Mailer\\Bridge\\Mailgun\\Transport\\MailgunTransportFactory" not found'
        ));

        $this->post('/forgot-password', ['email' => 'ama@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status', 'If that address has an account, a reset link is on its way.');
    }

    public function test_a_password_can_actually_be_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'ama@example.com',
            'must_change_password' => true,
        ]);

        $token = Password::createToken($user);

        $this->get('/reset-password/'.$token.'?email=ama@example.com')->assertOk();

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'ama@example.com',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect(route('login'));

        $user->refresh();

        $this->assertTrue(Hash::check('a-brand-new-password', $user->password));
        // Choosing their own settles the temporary one we issued — that flag
        // is what the portal's password gate reads.
        $this->assertFalse($user->must_change_password);
    }

    public function test_a_bad_token_is_refused(): void
    {
        User::factory()->create(['email' => 'ama@example.com']);

        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'ama@example.com',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_the_login_page_offers_the_link(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('password.request'), false);
    }
}
