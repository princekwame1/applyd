<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Mailgun only sends as a domain it has verified, so a visitor's own
     * address can only ever be Reply-To — putting it in From gets the message
     * refused, or delivered as something the receiving side treats as forged.
     * Read off the array transport rather than Mail::fake(), which doesn't
     * record raw sends.
     */
    public function test_the_visitor_is_reply_to_and_never_the_from_address(): void
    {
        $this->post(route('contact.submit'), [
            'name' => 'Ama Serwaa',
            'email' => 'ama@gmail.com',
            'subject' => 'Bootcamp dates',
            'message' => 'When does the next cohort start?',
        ])->assertRedirect(route('contact'));

        $messages = Mail::mailer()->getSymfonyTransport()->messages();

        $this->assertCount(1, $messages);

        $email = $messages[0]->getOriginalMessage();

        $this->assertSame([config('mail.from.address')], $this->addresses($email->getFrom()));
        $this->assertSame([config('mail.from.address')], $this->addresses($email->getTo()));
        $this->assertSame(['ama@gmail.com'], $this->addresses($email->getReplyTo()));
        $this->assertStringContainsString('ama@gmail.com', $email->getTextBody());
    }

    /**
     * A broken mailer must not 500 a visitor, and must not tell them their
     * message arrived. A missing transport bridge on the server throws an
     * Error rather than an Exception, which is exactly the case that used to
     * get through unguarded.
     */
    public function test_a_broken_mailer_is_reported_not_thrown(): void
    {
        Mail::shouldReceive('raw')->once()->andThrow(new \Error(
            'Class "Symfony\\Component\\Mailer\\Bridge\\Mailgun\\Transport\\MailgunTransportFactory" not found'
        ));

        $this->post(route('contact.submit'), [
            'name' => 'Ama Serwaa',
            'email' => 'ama@gmail.com',
            'subject' => 'Bootcamp dates',
            'message' => 'When does the next cohort start?',
        ])
            ->assertRedirect()
            ->assertSessionHas('contact_error')
            ->assertSessionMissing('contact_success');
    }

    /** @param  array<int, Address>  $addresses */
    protected function addresses(array $addresses): array
    {
        return array_map(fn ($address) => $address->getAddress(), $addresses);
    }
}
