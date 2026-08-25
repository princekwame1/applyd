<?php

namespace App\Exports;

use App\Models\CourseEnrollment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Everything held against a course registration, in one sheet.
 *
 * The two payments stay in separate columns — the form fee and tuition are
 * chased separately on the dashboard and a spreadsheet wants to total them
 * apart. The Paystack charge is its own column too: `amount` and
 * `tuition_amount` are what the academy earned, the fee is what the payer was
 * charged on top, and adding them together would misstate both.
 */
class CourseEnrollmentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return CourseEnrollment::with('course')->latest()->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Student ID',
            'Name',
            'Email',
            'Phone',
            'Course',
            'Attendance',
            'Application',
            // Application details, collected after the form fee is paid.
            'Date of Birth',
            'Gender',
            'Country',
            'City',
            'Education',
            'Goals',
            // Money in: the form fee.
            'Form Fee',
            'Form Status',
            'Form Charge (Paystack)',
            'Form Reference',
            'Form Paid At',
            // Money in: tuition.
            'Tuition Plan',
            'Tuition Status',
            'Tuition Paid',
            'Tuition Full',
            'Tuition Balance',
            'Tuition Charge (Paystack)',
            'Tuition Reference',
            'Tuition Paid At',
            // Credentials and chasing.
            'Serial No',
            'PIN',
            'Login Sent At',
            'Form Reminder Sent',
            'Tuition Reminder Sent',
        ];
    }

    public function map($e): array
    {
        return [
            $e->id,
            $e->created_at?->format('Y-m-d H:i'),
            $e->student_id,
            $e->name,
            $e->email,
            $e->phone,
            $e->course?->title,
            $e->attendance_type ? $e->attendance_label : null,
            $e->completed_at ? 'Completed' : 'Incomplete',

            $e->date_of_birth?->format('Y-m-d'),
            $e->gender,
            $e->country,
            $e->city,
            $e->education_level,
            $e->goals,

            number_format((float) $e->amount, 2, '.', ''),
            ucfirst((string) $e->status),
            number_format((float) $e->amount_fee, 2, '.', ''),
            $e->reference,
            $e->paid_at?->format('Y-m-d H:i'),

            $e->tuition_option,
            $e->tuition_status_label,
            number_format((float) $e->tuition_paid, 2, '.', ''),
            number_format($e->tuitionFull(), 2, '.', ''),
            number_format($e->tuitionBalance(), 2, '.', ''),
            number_format((float) $e->tuition_fee, 2, '.', ''),
            $e->tuition_reference,
            $e->tuition_paid_at?->format('Y-m-d H:i'),

            $e->serial_no,
            $e->pin,
            $e->credentials_sent_at?->format('Y-m-d H:i'),
            $e->form_reminder_sent_at?->format('Y-m-d H:i'),
            $e->tuition_reminder_sent_at?->format('Y-m-d H:i'),
        ];
    }
}
