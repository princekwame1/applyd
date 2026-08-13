<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /profile is shared by every signed-in user, so the navigation wrapped around
 * it has to match the role — a recruiter must not be handed the admin sidebar.
 */
class ProfileLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function recruiter(): User
    {
        $user = User::factory()->create();
        $user->assignRole('company');
        Company::create(['user_id' => $user->id, 'name' => 'Acme Ltd']);

        return $user;
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_a_recruiter_gets_the_company_nav_on_their_profile(): void
    {
        $this->actingAs($this->recruiter())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profile Settings')
            ->assertSee('Talent Pool')
            ->assertSee('Plans &amp; Credits', false);
    }

    public function test_a_recruiter_never_sees_the_admin_sections(): void
    {
        $response = $this->actingAs($this->recruiter())->get(route('profile.edit'))->assertOk();

        // Matched as the sidebar's own group markup — "Academy" on its own is
        // the brand name and appears legitimately in the company nav.
        foreach (['Bootcamp', 'Job Board', 'Academy', 'Website (CMS)', 'General'] as $group) {
            $response->assertDontSee('<span>'.$group.'</span>', false);
        }

        foreach (['Registrations', 'Pulse Check', 'Email Templates', 'Session Videos'] as $adminLink) {
            $response->assertDontSee($adminLink);
        }

        // And none of the dashboard URLs they would only get a 403 from.
        $response->assertDontSee(route('dashboard.registrations'));
        $response->assertDontSee(route('dashboard.tools'));
    }

    public function test_an_admin_still_gets_the_admin_sidebar(): void
    {
        $this->actingAs($this->admin())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profile Settings')
            ->assertSee('<span>Bootcamp</span>', false)
            ->assertSee(route('dashboard.registrations'));
    }

    public function test_a_recruiter_is_still_blocked_from_the_dashboard_itself(): void
    {
        // The layout swap is presentation. The routes stay guarded by the role.
        $this->actingAs($this->recruiter())
            ->get(route('dashboard.registrations'))
            ->assertForbidden();
    }
}
