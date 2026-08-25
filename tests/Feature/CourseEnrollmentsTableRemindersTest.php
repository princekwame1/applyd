<?php

namespace Tests\Feature;

use App\Livewire\CourseEnrollmentsTable;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseEnrollmentsTableRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super', 'web');
        Role::findOrCreate('student', 'web');
        config(['services.mnotify.api_key' => 'test-key', 'services.mnotify.sender_id' => 'Applyd']);
        Http::fake(['api.mnotify.com/*' => Http::response(['status' => 'success'], 200)]);

        $this->course = Course::create([
            'title' => 'Digital Marketing',
            'description' => 'x',
            'price' => 1000,
            'form_price' => 100,
        ]);
    }

    protected function make(array $attrs): CourseEnrollment
    {
        static $n = 0;
        $n++;

        return CourseEnrollment::create(array_merge([
            'course_id' => $this->course->id,
            'name' => 'Student '.$n,
            'email' => 'student'.$n.'@example.com',
            'phone' => '+23324083545'.($n % 10),
            'amount' => 100,
            'reference' => 'CRS-1-REF'.str_pad((string) $n, 7, '0', STR_PAD_LEFT),
        ], $attrs));
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super');

        return $admin;
    }

    public function test_form_fee_and_tuition_filters_split_the_two_payments(): void
    {
        $unpaidForm = $this->make(['status' => 'pending']);
        $owesTuition = $this->make(['status' => 'paid', 'tuition_status' => 'partial', 'tuition_amount' => 400]);
        $settled = $this->make(['status' => 'paid', 'tuition_status' => 'paid', 'tuition_amount' => 1000]);

        $table = Livewire::actingAs($this->admin())->test(CourseEnrollmentsTable::class);

        $table->set('filterComponents.form_fee', 'unpaid')
            ->assertSee($unpaidForm->name)
            ->assertDontSee($owesTuition->name)
            ->assertDontSee($settled->name);

        $table->set('filterComponents.form_fee', 'paid')
            ->assertDontSee($unpaidForm->name)
            ->assertSee($owesTuition->name)
            ->assertSee($settled->name);

        $table->set('filterComponents.form_fee', '')
            ->set('filterComponents.tuition', 'outstanding')
            ->assertSee($owesTuition->name)
            ->assertDontSee($settled->name)
            // Form fee unpaid means tuition is not outstanding YET.
            ->assertDontSee($unpaidForm->name);

        $table->set('filterComponents.tuition', 'paid')
            ->assertSee($settled->name)
            ->assertDontSee($owesTuition->name);
    }

    public function test_reminders_filter_finds_who_has_not_been_chased(): void
    {
        $chased = $this->make(['status' => 'pending', 'form_reminder_sent_at' => now()->subDay()]);
        $notChased = $this->make(['status' => 'pending']);

        Livewire::actingAs($this->admin())->test(CourseEnrollmentsTable::class)
            ->set('filterComponents.reminders', 'form_none')
            ->assertSee($notChased->name)
            ->assertDontSee($chased->name);
    }

    public function test_bulk_form_reminder_sends_to_debtors_and_skips_the_rest(): void
    {
        $owes = $this->make(['status' => 'pending']);
        $paid = $this->make(['status' => 'paid']);

        Livewire::actingAs($this->admin())->test(CourseEnrollmentsTable::class)
            ->set('selected', [(string) $owes->id, (string) $paid->id])
            ->call('performRemindFormFeeSelected');

        $this->assertSame(1, SmsLog::count());
        $this->assertNotNull($owes->fresh()->form_reminder_sent_at);
        $this->assertNull($paid->fresh()->form_reminder_sent_at);
    }

    public function test_bulk_tuition_reminder_only_goes_to_those_with_a_balance(): void
    {
        $owes = $this->make(['status' => 'paid', 'tuition_status' => 'partial', 'tuition_amount' => 400]);
        $settled = $this->make(['status' => 'paid', 'tuition_status' => 'paid', 'tuition_amount' => 1000]);
        $formUnpaid = $this->make(['status' => 'pending']);

        Livewire::actingAs($this->admin())->test(CourseEnrollmentsTable::class)
            ->set('selected', [(string) $owes->id, (string) $settled->id, (string) $formUnpaid->id])
            ->call('performRemindTuitionSelected');

        $this->assertSame(1, SmsLog::count());
        $this->assertNotNull($owes->fresh()->tuition_reminder_sent_at);
        $this->assertNull($settled->fresh()->tuition_reminder_sent_at);
        $this->assertNull($formUnpaid->fresh()->tuition_reminder_sent_at);
    }

    public function test_a_non_admin_cannot_fire_the_bulk_reminder_over_http(): void
    {
        // Livewire methods are publicly callable, so the route group is not the guard.
        $owes = $this->make(['status' => 'pending']);

        $student = User::factory()->create();
        $student->assignRole('student');

        Livewire::actingAs($student)->test(CourseEnrollmentsTable::class)
            ->set('selected', [(string) $owes->id])
            ->call('performRemindFormFeeSelected')
            ->assertForbidden();

        $this->assertSame(0, SmsLog::count());
    }
}
