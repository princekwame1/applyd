<?php

namespace Tests\Feature;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "Countries Reached" card on /dashboard/registrations and the per-country
 * breakdown behind it.
 */
class RegistrationCountriesTest extends TestCase
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

    public function test_the_card_counts_distinct_countries_and_lists_each_with_its_total(): void
    {
        $this->actingAs($this->admin());

        $this->registration(['country' => 'Ghana']);
        $this->registration(['country' => 'Ghana']);
        $this->registration(['country' => 'Ghana']);
        $this->registration(['country' => 'Nigeria']);
        $this->registration(['country' => 'Kenya']);

        $page = $this->get(route('dashboard.registrations'))->assertOk();

        $page->assertSeeInOrder(['Countries Reached', 'Registrations by country', 'Ghana', 'Kenya', 'Nigeria']);

        // Biggest first, each with its own count.
        $this->assertSame(
            ['Ghana' => 3, 'Kenya' => 1, 'Nigeria' => 1],
            $page->viewData('countryCounts')->all(),
        );
        $this->assertSame(3, $page->viewData('stats')['countries']);
    }

    public function test_a_blank_country_is_not_counted_as_one(): void
    {
        $this->actingAs($this->admin());

        $this->registration(['country' => 'Ghana']);
        $this->registration(['country' => '']);

        $page = $this->get(route('dashboard.registrations'))->assertOk();

        // The card and the list are driven by the same query, so an empty
        // country can't inflate one without showing up in the other.
        $this->assertSame(1, $page->viewData('stats')['countries']);
        $this->assertSame(['Ghana' => 1], $page->viewData('countryCounts')->all());
    }

    public function test_with_no_registrations_there_is_nothing_to_hover(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('dashboard.registrations'))
            ->assertOk()
            ->assertSee('Countries Reached')
            ->assertDontSee('Registrations by country');
    }
}
