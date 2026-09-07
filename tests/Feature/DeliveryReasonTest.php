<?php

namespace Tests\Feature;

use App\Livewire\EmailLogsTable;
use App\Livewire\SmsLogsTable;
use App\Models\EmailLog;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The delivery screens have to answer the question they exist for: when a
 * message did not arrive, why not. The gateway's own words were being stored
 * on the row and shown nowhere.
 */
class DeliveryReasonTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_email_delivery_shows_why_a_send_failed(): void
    {
        EmailLog::create([
            'email' => 'ama@example.com',
            'subject' => 'Your download',
            'body' => 'x',
            'status' => 'failed',
            'response' => 'Mailgun: Domain info.applydacademy.com is not allowed to send',
        ]);

        Livewire::actingAs($this->admin())
            ->test(EmailLogsTable::class)
            ->assertSee('Domain info.applydacademy.com is not allowed to send');
    }

    /** A successful send says nothing — "Accepted by ..." on every row hides the one that matters. */
    public function test_a_successful_send_shows_no_reason(): void
    {
        EmailLog::create([
            'email' => 'ama@example.com',
            'subject' => 'Your download',
            'body' => 'x',
            'status' => 'sent',
            'response' => 'Accepted by mailgun mailer',
        ]);

        Livewire::actingAs($this->admin())
            ->test(EmailLogsTable::class)
            ->assertDontSee('Accepted by mailgun mailer');
    }

    public function test_sms_delivery_shows_why_a_send_failed(): void
    {
        SmsLog::create([
            'phone_number' => '+233240000000',
            'message' => 'hi',
            'status' => 'failed',
            'response' => 'Gateway rejected: insufficient balance',
        ]);

        Livewire::actingAs($this->admin())
            ->test(SmsLogsTable::class)
            ->assertSee('Gateway rejected: insufficient balance');
    }
}
