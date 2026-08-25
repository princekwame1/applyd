<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Support\Str;

/**
 * Payment reminders by SMS — the single place the "you still owe us" messages
 * are composed and sent, for both the application form fee and tuition.
 *
 * Two rules run through everything here:
 *
 * 1. Someone who does not owe the money is SKIPPED, never sent. A reminder to
 *    a student who already paid reads as "we lost your payment", which costs
 *    more to undo than the reminder was ever worth. The caller reports the
 *    skipped count so a drop never looks like a delivery failure.
 * 2. The message is one GSM-7 segment. A single character outside that alphabet
 *    — an em dash, a curly quote — flips the whole SMS to UCS-2, where a
 *    segment is 70 characters instead of 160. Plain ASCII, and no course title:
 *    it is the one part with no length limit, and the student knows what they
 *    applied for.
 */
class PaymentReminderService
{
    /** One GSM-7 SMS. Past this the message silently costs double. */
    public const SEGMENT = 160;

    /** A long name must not be what pushes the message into a second segment. */
    protected const NAME_LIMIT = 18;

    public function __construct(protected SmsNotificationService $sms) {}

    /**
     * Remind someone to pay the application form fee.
     *
     * Returns false when there was nothing to remind them about, so the caller
     * can tell "skipped" apart from "the gateway refused it".
     */
    public function sendFormFeeReminder(CourseEnrollment $enrollment): bool
    {
        if (! $enrollment->owesFormFee()) {
            return false;
        }

        $sent = $this->sms->send(
            $enrollment->phone,
            $this->formFeeMessage($enrollment),
            null,
            $enrollment->name,
        );

        // Stamped whether or not the gateway accepted it: the question this
        // column answers on the table is "have we already chased this person?",
        // and the delivery outcome is on the SMS log.
        $enrollment->forceFill(['form_reminder_sent_at' => now()])->save();

        return $sent;
    }

    /** Remind someone to pay tuition they still owe. */
    public function sendTuitionReminder(CourseEnrollment $enrollment): bool
    {
        if (! $enrollment->owesTuition()) {
            return false;
        }

        $sent = $this->sms->send(
            $enrollment->phone,
            $this->tuitionMessage($enrollment),
            null,
            $enrollment->name,
        );

        $enrollment->forceFill(['tuition_reminder_sent_at' => now()])->save();

        return $sent;
    }

    public function formFeeMessage(CourseEnrollment $enrollment): string
    {
        return sprintf(
            'Hi %s, your Applyd Academy form fee (%s) is still unpaid. Pay here to continue your application: %s',
            $this->firstName($enrollment),
            Course::money((float) $enrollment->amount),
            $enrollment->payUrl(),
        );
    }

    public function tuitionMessage(CourseEnrollment $enrollment): string
    {
        return sprintf(
            'Hi %s, your Applyd Academy tuition balance is %s. Pay here to complete your registration: %s',
            $this->firstName($enrollment),
            Course::money($enrollment->tuitionBalance()),
            $enrollment->payUrl(),
        );
    }

    /**
     * First name only, trimmed to something a message can carry, and stripped
     * to plain ASCII — an accented character in a name would take the whole SMS
     * to UCS-2 and halve the room left for the link.
     */
    protected function firstName(CourseEnrollment $enrollment): string
    {
        $name = Str::ascii($enrollment->first_name);
        $name = preg_replace('/[^A-Za-z0-9 \'-]/', '', $name) ?: 'there';

        return Str::limit(trim($name), self::NAME_LIMIT, '');
    }
}
