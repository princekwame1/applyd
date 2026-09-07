<?php

namespace Tests\Feature;

use App\Livewire\FacilitatorsTable;
use App\Models\EmailLog;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\FacilitatorAccountService;
use App\Support\Portal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Getting a facilitator into the learning portal.
 *
 * The rules are the student ones, because it is the same problem: the account
 * belongs to the person, a password that already works is never reset, and a
 * resend can only ever be a new temporary password.
 */
class FacilitatorAccountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    private function facilitator(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'name' => 'Kwame Boateng',
            'email' => 'kwame@example.com',
            'phone' => '0240000000',
            'must_change_password' => true,
        ], $attributes));

        $user->assignRole('lecturer');

        return $user;
    }

    /* ------------------------------------------------------------ creating */

    public function test_adding_a_facilitator_creates_the_account_and_sends_the_login(): void
    {
        $this->actingAs($this->admin())
            ->post('/dashboard/facilitators', [
                'name' => 'Kwame Boateng',
                'email' => 'Kwame@Example.com',
                'phone' => '0240835458',
                'send_credentials' => '1',
            ])
            ->assertRedirect(route('dashboard.facilitators'));

        $user = User::where('email', 'kwame@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('lecturer'));
        $this->assertTrue($user->must_change_password);
        $this->assertNotNull($user->credentials_sent_at);
        $this->assertSame(1, EmailLog::where('template_key', 'facilitator_credentials')->count());
        $this->assertSame(1, SmsLog::count());
    }

    public function test_a_facilitator_can_be_added_without_sending_anything_yet(): void
    {
        $this->actingAs($this->admin())
            ->post('/dashboard/facilitators', [
                'name' => 'Ama Mensah',
                'email' => 'ama@example.com',
                'send_credentials' => '0',
            ]);

        $user = User::where('email', 'ama@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('lecturer'));
        $this->assertNull($user->credentials_sent_at);
        $this->assertSame(0, EmailLog::count());
    }

    /** The account belongs to the person: an existing login gains the role, unchanged. */
    public function test_an_existing_account_is_granted_access_and_keeps_its_password(): void
    {
        $existing = User::factory()->create([
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'password' => Hash::make('her-own-password'),
            'must_change_password' => false,
        ]);
        $existing->assignRole('student');

        $this->actingAs($this->admin())
            ->post('/dashboard/facilitators', [
                'name' => 'Ama Mensah',
                'email' => 'ama@example.com',
                'send_credentials' => '1',
            ]);

        $existing->refresh();

        $this->assertSame(1, User::where('email', 'ama@example.com')->count());
        $this->assertTrue($existing->hasRole('lecturer'));
        // Still a student too — teaching does not take away what they were.
        $this->assertTrue($existing->hasRole('student'));
        $this->assertTrue(Hash::check('her-own-password', $existing->password));
        $this->assertFalse($existing->must_change_password);
    }

    /* ------------------------------------------------------------ resending */

    public function test_a_resend_mints_a_new_password_only_for_someone_still_on_ours(): void
    {
        $facilitator = $this->facilitator();
        $before = $facilitator->password;

        $this->actingAs($this->admin())
            ->from(route('dashboard.facilitators'))
            ->post('/dashboard/facilitators/'.$facilitator->id.'/credentials')
            ->assertRedirect(route('dashboard.facilitators'));

        $this->assertNotSame($before, $facilitator->fresh()->password);
        $this->assertSame(1, EmailLog::where('template_key', 'facilitator_credentials')->count());
    }

    public function test_a_resend_leaves_a_chosen_password_alone(): void
    {
        $facilitator = $this->facilitator([
            'password' => Hash::make('his-own-password'),
            'must_change_password' => false,
        ]);

        $this->actingAs($this->admin())
            ->from(route('dashboard.facilitators'))
            ->post('/dashboard/facilitators/'.$facilitator->id.'/credentials');

        $this->assertTrue(Hash::check('his-own-password', $facilitator->fresh()->password));
        $this->assertSame(1, EmailLog::where('template_key', 'facilitator_credentials')->count());
    }

    public function test_the_bulk_send_reaches_every_ticked_facilitator(): void
    {
        $one = $this->facilitator();
        $two = $this->facilitator(['email' => 'ama@example.com', 'name' => 'Ama Mensah']);

        Livewire::actingAs($this->admin())
            ->test(FacilitatorsTable::class)
            ->set('selected', [(string) $one->id, (string) $two->id])
            ->call('performSendCredentialsSelected');

        $this->assertSame(2, EmailLog::where('template_key', 'facilitator_credentials')->count());
        $this->assertNotNull($one->fresh()->credentials_sent_at);
        $this->assertNotNull($two->fresh()->credentials_sent_at);
    }

    /** A send that failed must say so, not read as a delivery. */
    public function test_a_failed_send_is_reported_rather_than_reading_as_sent(): void
    {
        $facilitator = $this->facilitator();

        // No from address is what a half-configured mail block looks like, and
        // EmailNotificationService records exactly that on the log row.
        config(['mail.from.address' => null]);

        $this->actingAs($this->admin())
            ->from(route('dashboard.facilitators'))
            ->post('/dashboard/facilitators/'.$facilitator->id.'/credentials')
            ->assertRedirect(route('dashboard.facilitators'))
            ->assertSessionHas('error');

        $this->assertSame('failed', EmailLog::where('template_key', 'facilitator_credentials')->firstOrFail()->status);
        // Still stamped: the question it answers is "have we tried?", and the
        // outcome lives on the log.
        $this->assertNotNull($facilitator->fresh()->credentials_sent_at);
    }

    /* ---------------------------------------------------------------- SMS */

    /**
     * One GSM-7 segment. Pinned against a longer host and a long first name
     * than production has, so the headroom is real rather than a coincidence
     * of today's PORTAL_URL.
     */
    public function test_the_sms_stays_within_one_gsm7_segment(): void
    {
        config(['services.portal.url' => 'https://students.applydacademy.com.gh']);

        $facilitator = $this->facilitator(['name' => 'Akosua Nyarkoaa Boatemaa']);
        $service = app(FacilitatorAccountService::class);

        $method = new \ReflectionMethod($service, 'smsMessage');

        foreach ([null, 'R4KQ7MTXVP'] as $password) {
            $message = $method->invoke($service, $facilitator, $password);

            $this->assertLessThanOrEqual(160, strlen($message), 'Message: '.$message);
            $this->assertSame($message, mb_convert_encoding($message, 'ASCII', 'UTF-8'), 'Non-ASCII in: '.$message);
            $this->assertStringContainsString(Portal::loginUrl(), $message);
        }
    }

    /* ------------------------------------------------------------- access */

    public function test_revoking_closes_the_door_without_deleting_anything(): void
    {
        $facilitator = $this->facilitator();

        $this->actingAs($this->admin())
            ->delete('/dashboard/facilitators/'.$facilitator->id)
            ->assertRedirect(route('dashboard.facilitators'));

        $facilitator->refresh();

        $this->assertFalse($facilitator->hasRole('lecturer'));
        $this->assertDatabaseHas('users', ['id' => $facilitator->id]);
    }

    public function test_you_cannot_revoke_your_own_access(): void
    {
        $admin = $this->admin();
        $admin->assignRole('lecturer');

        $this->actingAs($admin)
            ->from(route('dashboard.facilitators'))
            ->delete('/dashboard/facilitators/'.$admin->id)
            ->assertRedirect(route('dashboard.facilitators'));

        $this->assertTrue($admin->fresh()->hasRole('lecturer'));
    }

    public function test_only_facilitators_are_reachable_through_these_routes(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $this->actingAs($this->admin())->get('/dashboard/facilitators/'.$student->id.'/edit')->assertNotFound();
        $this->actingAs($this->admin())->post('/dashboard/facilitators/'.$student->id.'/credentials')->assertNotFound();
    }

    public function test_the_screens_render_and_are_admin_only(): void
    {
        $facilitator = $this->facilitator();

        $this->get('/dashboard/facilitators')->assertRedirect(route('login'));

        $admin = $this->admin();
        $this->actingAs($admin)->get('/dashboard/facilitators')->assertOk()->assertSee('Kwame Boateng');
        $this->actingAs($admin)->get('/dashboard/facilitators/'.$facilitator->id.'/edit')->assertOk();
    }
}
