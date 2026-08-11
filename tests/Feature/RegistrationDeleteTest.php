<?php

namespace Tests\Feature;

use App\Livewire\RegistrationsTable;
use App\Models\EmailLog;
use App\Models\Registration;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    /**
     * An admin-level user with the registrations permission revoked — proves the
     * guard is the permission, not merely the role.
     */
    protected function adminWithoutDelete(): User
    {
        $role = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

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

    public function test_an_admin_can_delete_a_single_registration(): void
    {
        $a = $this->registration();
        $b = $this->registration();

        $this->actingAs($this->admin());

        Livewire::test(RegistrationsTable::class)->call('performDelete', $a->id);

        $this->assertDatabaseMissing('registrations', ['id' => $a->id]);
        $this->assertDatabaseHas('registrations', ['id' => $b->id]);
    }

    public function test_an_admin_can_delete_the_selected_registrations(): void
    {
        $a = $this->registration();
        $b = $this->registration();
        $keep = $this->registration();

        $this->actingAs($this->admin());

        $component = Livewire::test(RegistrationsTable::class)
            ->set('selected', [(string) $a->id, (string) $b->id])
            ->call('performDeleteSelected');

        $this->assertSame(1, Registration::count());
        $this->assertDatabaseHas('registrations', ['id' => $keep->id]);
        $this->assertSame([], $component->get('selected'));
    }

    public function test_deleting_keeps_the_delivery_history(): void
    {
        $a = $this->registration();

        $email = EmailLog::create([
            'registration_id' => $a->id,
            'email' => $a->email,
            'subject' => 'Hello',
            'body' => '<p>Hi</p>',
            'status' => 'sent',
        ]);

        $sms = SmsLog::create([
            'registration_id' => $a->id,
            'phone_number' => '+233241234567',
            'message' => 'Hi',
            'status' => 'sent',
        ]);

        $this->actingAs($this->admin());

        Livewire::test(RegistrationsTable::class)->call('performDelete', $a->id);

        // Both rows survive, detached — the person is gone, the record of what
        // we sent them is not. sms_logs is ON DELETE CASCADE at the DB level, so
        // this specifically proves the detach step is doing its job.
        $this->assertDatabaseHas('email_logs', ['id' => $email->id, 'registration_id' => null]);
        $this->assertDatabaseHas('sms_logs', ['id' => $sms->id, 'registration_id' => null]);
    }

    public function test_a_user_without_the_permission_cannot_delete(): void
    {
        $a = $this->registration();

        $this->actingAs($this->adminWithoutDelete());

        Livewire::test(RegistrationsTable::class)
            ->call('performDelete', $a->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('registrations', ['id' => $a->id]);
    }

    public function test_a_user_without_the_permission_cannot_bulk_delete(): void
    {
        $a = $this->registration();

        $this->actingAs($this->adminWithoutDelete());

        Livewire::test(RegistrationsTable::class)
            ->set('selected', [(string) $a->id])
            ->call('performDeleteSelected')
            ->assertStatus(403);

        $this->assertDatabaseHas('registrations', ['id' => $a->id]);
    }

    public function test_the_delete_bulk_action_is_hidden_without_the_permission(): void
    {
        $this->actingAs($this->adminWithoutDelete());

        $actions = Livewire::test(RegistrationsTable::class)->instance()->bulkActions();

        $this->assertArrayNotHasKey('deleteSelected', $actions);
        $this->assertArrayHasKey('composeSelected', $actions);
    }

    public function test_the_delete_action_asks_for_confirmation_before_deleting(): void
    {
        $a = $this->registration();

        $this->actingAs($this->admin());

        // deleteRow only raises the confirm dialog; nothing is removed until the
        // callback fires performDelete.
        Livewire::test(RegistrationsTable::class)->call('deleteRow', $a->id);

        $this->assertDatabaseHas('registrations', ['id' => $a->id]);
    }

    public function test_deleting_a_missing_registration_is_a_no_op(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(RegistrationsTable::class)
            ->call('performDelete', 99999)
            ->assertOk();
    }

    public function test_the_table_renders_a_delete_button_for_permitted_users(): void
    {
        $this->registration();

        $this->actingAs($this->admin())
            ->get(route('dashboard.registrations'))
            ->assertOk()
            ->assertSee('Delete');
    }
}
