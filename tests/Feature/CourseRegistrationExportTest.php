<?php

namespace Tests\Feature;

use App\Exports\CourseEnrollmentsExport;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class CourseRegistrationExportTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    protected function enrollment(): CourseEnrollment
    {
        $course = Course::create([
            'title' => 'Data Analytics',
            'slug' => 'data-analytics',
            'price' => 100,
            'is_published' => true,
        ]);

        return CourseEnrollment::create([
            'course_id' => $course->id,
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'phone' => '+233241234567',
            'amount' => 50,
            'amount_fee' => 1.95,
            'status' => 'paid',
            'paid_at' => now(),
            'reference' => 'REF-1',
            'student_id' => '20260001',
            'serial_no' => 'APPLYD1234567',
            'pin' => '1234',
            'credentials_sent_at' => now(),
            'tuition_amount' => 250,
            'tuition_status' => 'partial',
            'gender' => 'Female',
            'country' => 'Ghana',
            'city' => 'Accra',
            'education_level' => "Bachelor's Degree",
            'completed_at' => now(),
        ]);
    }

    public function test_an_admin_can_download_the_course_registrations_sheet(): void
    {
        Excel::fake();

        $this->actingAs($this->admin())
            ->get(route('dashboard.course-registrations.export'))
            ->assertOk();

        Excel::assertDownloaded('course-registrations-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_a_guest_cannot(): void
    {
        $this->get(route('dashboard.course-registrations.export'))->assertRedirect(route('login'));
    }

    public function test_the_sheet_carries_the_student_id_and_both_payments_separately(): void
    {
        $enrollment = $this->enrollment();

        $export = new CourseEnrollmentsExport;
        $headings = $export->headings();
        $row = $export->map($enrollment->fresh()->load('course'));

        $this->assertSameSize($headings, $row, 'Every heading needs a value under it.');

        $sheet = array_combine($headings, $row);

        $this->assertSame('20260001', $sheet['Student ID']);
        $this->assertSame('Ama Mensah', $sheet['Name']);
        $this->assertSame('Data Analytics', $sheet['Course']);

        // What the academy earned and what the payer was charged stay apart.
        $this->assertSame('50.00', $sheet['Form Fee']);
        $this->assertSame('1.95', $sheet['Form Charge (Paystack)']);

        // Tuition is its own money, with the outstanding balance worked out.
        $this->assertSame('250.00', $sheet['Tuition Paid']);
        $this->assertSame('100.00', $sheet['Tuition Full']);
        $this->assertSame('0.00', $sheet['Tuition Balance']);
        $this->assertSame('Part payment (50%)', $sheet['Tuition Status']);

        $this->assertSame('Completed', $sheet['Application']);
        $this->assertNotNull($sheet['Login Sent At']);
        $this->assertSame('Ghana', $sheet['Country']);
    }
}
