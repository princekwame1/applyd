<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Impersonation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * An admin signing in as somebody else to see what they see.
 *
 * The rule that matters most is that impersonation only ever goes *downwards*:
 * taking over a senior account would be a way to grant yourself the permission
 * you were deliberately not given.
 */
class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_an_admin_can_view_the_site_as_another_user(): void
    {
        $admin = $this->user('admin');
        $student = $this->user('student');

        $this->actingAs($admin)
            ->post(route('dashboard.users.impersonate', $student))
            ->assertRedirect();

        // The session is now the student's, with the admin remembered underneath.
        $this->assertSame($student->id, Auth::id());
        $this->assertTrue(Impersonation::active());
        $this->assertSame($admin->id, Impersonation::impersonator()->id);
    }

    public function test_the_banner_offers_a_way_back_from_any_page(): void
    {
        $admin = $this->user('admin');
        $company = $this->user('company');

        $this->actingAs($admin)->post(route('dashboard.users.impersonate', $company));

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Viewing as')
            ->assertSee('Stop impersonating');
    }

    public function test_stopping_hands_the_session_back(): void
    {
        $admin = $this->user('admin');
        $student = $this->user('student');

        $this->actingAs($admin)->post(route('dashboard.users.impersonate', $student));
        $this->post(route('impersonate.stop'))->assertRedirect(route('dashboard'));

        $this->assertSame($admin->id, Auth::id());
        $this->assertFalse(Impersonation::active());
    }

    public function test_an_admin_cannot_impersonate_upwards(): void
    {
        $admin = $this->user('admin');
        $super = $this->user('super');

        $this->actingAs($admin)
            ->post(route('dashboard.users.impersonate', $super))
            ->assertSessionHas('error');

        // Otherwise this is a way to award yourself the one role you lack.
        $this->assertSame($admin->id, Auth::id());
        $this->assertFalse(Impersonation::active());
    }

    public function test_a_super_can_impersonate_an_admin(): void
    {
        $super = $this->user('super');
        $admin = $this->user('admin');

        $this->actingAs($super)->post(route('dashboard.users.impersonate', $admin));

        $this->assertSame($admin->id, Auth::id());
    }

    public function test_nobody_else_can_impersonate_at_all(): void
    {
        $student = $this->user('student');
        $target = $this->user('company');

        $this->actingAs($student)
            ->post(route('dashboard.users.impersonate', $target))
            ->assertForbidden();

        $this->assertSame($student->id, Auth::id());
    }

    public function test_a_guest_cannot_impersonate(): void
    {
        $target = $this->user('student');

        $this->post(route('dashboard.users.impersonate', $target))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_impersonation_cannot_be_chained(): void
    {
        $admin = $this->user('admin');
        $first = $this->user('student');
        $second = $this->user('company');

        $this->actingAs($admin)->post(route('dashboard.users.impersonate', $first));

        // Now signed in as a student, who has no business starting one — and a
        // chain would make "who did this?" unanswerable anyway.
        $this->post(route('dashboard.users.impersonate', $second))->assertForbidden();

        $this->assertSame($first->id, Auth::id());
        $this->assertSame($admin->id, Impersonation::impersonator()->id);
    }

    public function test_stopping_when_nothing_is_being_impersonated_is_harmless(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->post(route('impersonate.stop'))->assertRedirect(route('dashboard'));

        $this->assertSame($admin->id, Auth::id());
    }

    public function test_the_impersonated_account_is_never_written_to(): void
    {
        $admin = $this->user('admin');
        $student = $this->user('student');
        $before = [
            'password' => $student->password,
            'email' => $student->email,
            'name' => $student->name,
            'updated_at' => (string) $student->updated_at,
        ];

        $this->actingAs($admin)->post(route('dashboard.users.impersonate', $student));
        $this->post(route('impersonate.stop'));

        // Nothing about them changes, so an abandoned impersonation — or a
        // session that simply expires — leaves no trace on their account.
        $after = $student->fresh();

        $this->assertSame($before, [
            'password' => $after->password,
            'email' => $after->email,
            'name' => $after->name,
            'updated_at' => (string) $after->updated_at,
        ]);
    }

    public function test_the_button_only_shows_for_someone_who_may_use_it(): void
    {
        $admin = $this->user('admin');
        $admin->givePermissionTo('manage users');
        $super = $this->user('super');
        $student = $this->user('student');

        $page = $this->actingAs($admin)->get(route('dashboard.users'))->assertOk();

        // Offered for the ordinary account, not for the senior one.
        $page->assertSee(route('dashboard.users.impersonate', $student), false);
        $page->assertDontSee(route('dashboard.users.impersonate', $super), false);
    }
}
