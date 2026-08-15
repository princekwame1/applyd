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
     * The cPanel relay answers 550 to anything whose From header isn't on the
     * domain it authenticated as ("your domain gmail.com is not allowed in
     * header From"), so a visitor's own address can only ever be Reply-To.
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

    /** @param  array<int, Address>  $addresses */
    protected function addresses(array $addresses): array
    {
        return array_map(fn ($address) => $address->getAddress(), $addresses);
    }
}
