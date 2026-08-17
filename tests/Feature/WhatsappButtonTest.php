<?php

namespace Tests\Feature;

use App\Models\PageContent;
use App\Models\User;
use App\Support\Cms;
use App\Support\Whatsapp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The floating "chat with us" button on the public site.
 */
class WhatsappButtonTest extends TestCase
{
    use RefreshDatabase;

    protected function setStored(string $key, ?string $value): void
    {
        PageContent::updateOrCreate(['page' => 'site', 'key' => $key], ['value' => $value]);
        Cms::flush();
    }

    public function test_a_local_number_is_turned_into_the_form_wa_me_needs(): void
    {
        // wa.me takes digits only, in full international form. The leading 0 is
        // a local trunk prefix and reaches nobody once the country code is on.
        $this->assertSame('233240835458', Whatsapp::normalise('0240835458'));
        $this->assertSame('233240835458', Whatsapp::normalise('+233 24 083 5458'));
        $this->assertSame('233240835458', Whatsapp::normalise('024-083-5458'));
        $this->assertSame('233240835458', Whatsapp::normalise('00233240835458'));
        $this->assertSame('233240835458', Whatsapp::normalise('233240835458'));
        $this->assertSame('', Whatsapp::normalise(null));
        $this->assertSame('', Whatsapp::normalise('not a number'));
    }

    public function test_the_button_is_on_every_public_page(): void
    {
        foreach (['landing', 'about', 'contact', 'jobs'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('class="wa-float"', false)
                ->assertSee('https://wa.me/233240835458', false);
        }
    }

    public function test_the_link_carries_a_greeting_and_opens_safely(): void
    {
        $page = $this->get(route('landing'))->assertOk();

        $page->assertSee('wa.me/233240835458?text=', false)
            ->assertSee('rel="noopener"', false)
            ->assertSee('target="_blank"', false)
            // The link needs its own accessible name — the image is decorative.
            ->assertSee('aria-label="Chat with us on WhatsApp"', false)
            ->assertSee('alt=""', false);
    }

    public function test_an_admin_can_change_the_number_and_the_greeting(): void
    {
        $this->setStored('whatsapp_number', '0501112222');
        $this->setStored('whatsapp_message', 'Hi there');

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('https://wa.me/233501112222?text=Hi%20there', false)
            ->assertDontSee('wa.me/233240835458', false);
    }

    public function test_clearing_the_number_hides_the_button_altogether(): void
    {
        $this->setStored('whatsapp_number', '');

        $this->assertFalse(Whatsapp::enabled());

        // Not a dead button that opens a chat with nobody — no button at all.
        $this->get(route('landing'))
            ->assertOk()
            ->assertDontSee('wa-float', false)
            ->assertDontSee('wa.me', false);
    }

    public function test_the_button_stays_off_the_admin_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('wa-float', false);
    }

    public function test_the_number_and_wording_are_editable_from_the_cms_screen(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('dashboard.cms.edit', 'site'))
            ->assertOk()
            ->assertSee('WhatsApp number')
            ->assertSee('0240835458');
    }
}
