<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyPosterTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_a_guest_cannot_reach_the_poster(): void
    {
        $this->get(route('dashboard.surveys.poster'))->assertRedirect(route('login'));
    }

    public function test_the_poster_carries_the_public_survey_url(): void
    {
        $this->actingAs($this->admin());

        // The QR is drawn from this URL in the browser, so the URL being right
        // on the page is what actually matters here.
        $this->get(route('dashboard.surveys.poster', ['survey' => 'post']))
            ->assertOk()
            ->assertSee('Before you go')
            ->assertSee(route('surveys.show', 'post'));
    }

    public function test_the_poster_defaults_to_the_first_survey(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('dashboard.surveys.poster'))
            ->assertOk()
            ->assertSee(route('surveys.show', 'pre'));
    }

    public function test_the_results_page_offers_the_link_and_poster(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('dashboard.surveys'))
            ->assertOk()
            ->assertSee(route('surveys.show', 'pre'))
            ->assertSee(route('dashboard.surveys.poster', ['survey' => 'pre']));
    }
}
