<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Impersonation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImpersonationEscapeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['student', 'admin', 'super'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    public function test_stop_works_over_get_so_it_can_be_typed_into_the_address_bar(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super');

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->actingAs($admin)
            ->post(route('dashboard.users.impersonate', $student))
            ->assertRedirect();

        $this->assertTrue(Impersonation::active());
        $this->assertSame($student->id, auth()->id());

        // No CSRF token, no form — just the address.
        $this->get(route('impersonate.stop.get'))->assertRedirect();

        $this->assertFalse(Impersonation::active());
        $this->assertSame($admin->id, auth()->id());
    }

    public function test_the_403_an_impersonated_student_hits_carries_the_way_back(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super');

        $student = User::factory()->create();
        $student->assignRole('student');

        $this->actingAs($admin)->post(route('dashboard.users.impersonate', $student));

        // The exact dead end: a student cannot enter the admin dashboard.
        $res = $this->get(route('dashboard'));
        $res->assertForbidden();

        $res->assertSee(route('impersonate.stop.get'), false);
        $res->assertSee('Stop impersonating', false);
    }

    public function test_stopping_when_nothing_is_being_impersonated_is_a_harmless_noop(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super');

        $this->actingAs($admin)->get(route('impersonate.stop.get'))->assertRedirect();

        $this->assertSame($admin->id, auth()->id());
    }
}
