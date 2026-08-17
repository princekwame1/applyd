<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Support\PaystackFees;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Passing Paystack's charge on to the payer.
 *
 * The whole thing turns on one point: adding the fee to the bill is not enough,
 * because Paystack takes its cut of whatever is charged. The bill has to be
 * grossed up, and these check that it is — and that the extra never leaks into
 * the figures that mean "what this is worth to the academy".
 */
class PaystackFeeTest extends TestCase
{
    use RefreshDatabase;

    protected function passOn(float $percent = 1.95, float $fixed = 0, $cap = null): void
    {
        config([
            'services.paystack.fee.pass_on' => true,
            'services.paystack.fee.percent' => $percent,
            'services.paystack.fee.fixed' => $fixed,
            'services.paystack.fee.cap' => $cap,
        ]);
    }

    public function test_the_charge_is_grossed_up_so_the_price_arrives_in_full(): void
    {
        $this->passOn(1.95);

        $gross = PaystackFees::gross(100.0);

        // 100 / (1 - 0.0195) = 101.9887…, rounded up to the pesewa.
        $this->assertSame(101.99, $gross);

        // The point of grossing up: Paystack's cut of the charged figure still
        // leaves the full 100 behind.
        $this->assertGreaterThanOrEqual(100.0, round($gross - ($gross * 0.0195), 2));

        // Naively adding the fee to the price would not have.
        $this->assertLessThan(100.0, round(101.95 - (101.95 * 0.0195), 2));
    }

    public function test_a_fixed_component_is_grossed_up_too(): void
    {
        $this->passOn(1.5, 1.00);

        // (50 + 1) / (1 - 0.015) = 51.7766… → 51.78
        $this->assertSame(51.78, PaystackFees::gross(50.0));
        $this->assertSame(1.78, PaystackFees::fee(50.0));
    }

    public function test_the_cap_limits_what_a_large_payment_is_charged(): void
    {
        $this->passOn(1.95, 0, 100);

        // Uncapped this would add ~198; the cap holds it at 100.
        $this->assertSame(10100.0, PaystackFees::gross(10000.0));
        $this->assertSame(100.0, PaystackFees::fee(10000.0));

        // A small payment is nowhere near the cap, so it is unaffected.
        $this->assertSame(101.99, PaystackFees::gross(100.0));
    }

    public function test_rounding_always_favours_the_academy_not_the_shortfall(): void
    {
        $this->passOn(1.95);

        // Rounding down would leave us a fraction short on every transaction.
        foreach ([1.0, 7.35, 19.99, 250.0, 999.99] as $net) {
            $gross = PaystackFees::gross($net);

            $this->assertGreaterThanOrEqual($net, round($gross * (1 - 0.0195), 2));
            $this->assertSame($gross, round($gross, 2), 'charged amount must be whole pesewas');
        }
    }

    public function test_nothing_is_added_when_the_charge_is_not_passed_on(): void
    {
        config(['services.paystack.fee.pass_on' => false]);

        $this->assertSame(100.0, PaystackFees::gross(100.0));
        $this->assertSame(0.0, PaystackFees::fee(100.0));
        $this->assertFalse(PaystackFees::passedOn());
    }

    public function test_a_free_or_nonsense_amount_is_left_alone(): void
    {
        $this->passOn(1.95);

        $this->assertSame(0.0, PaystackFees::gross(0.0));
        $this->assertSame(0.0, PaystackFees::fee(0.0));

        // A rate of 100% has no grossed-up answer; bill the net rather than
        // divide by zero.
        $this->passOn(100);
        $this->assertSame(100.0, PaystackFees::gross(100.0));
    }

    public function test_the_net_can_be_recovered_from_what_was_charged(): void
    {
        $this->passOn(1.95);

        foreach ([50.0, 100.0, 1234.56] as $net) {
            $recovered = PaystackFees::netFrom(PaystackFees::gross($net));

            // Within a pesewa — which is why the exact base also travels in the
            // transaction metadata, and this is only the fallback.
            $this->assertEqualsWithDelta($net, $recovered, 0.01);
        }
    }

    public function test_the_registration_form_records_the_fee_apart_from_the_fee_earned(): void
    {
        $this->passOn(1.95);

        $course = Course::create(['title' => 'Data Analytics', 'description' => 'x', 'form_price' => 50]);

        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id,
            'name' => 'Ama', 'email' => 'ama@example.com', 'phone' => '+233240000000',
            'amount' => (float) $course->form_fee,
            'amount_fee' => PaystackFees::fee((float) $course->form_fee),
            'reference' => 'CRS-TEST', 'status' => 'pending',
        ]);

        // 50 / (1 - 0.0195) = 50.9944…, rounded up to 51.00 — so the charge is
        // a round 1.00. `amount` stays what the academy earns; the charge sits
        // beside it rather than inflating it.
        $this->assertSame('50.00', $enrollment->amount);
        $this->assertSame('1.00', $enrollment->amount_fee);
        $this->assertSame(51.00, PaystackFees::gross(50.0));
    }

    public function test_a_tuition_payment_credits_the_tuition_and_not_the_charge(): void
    {
        $this->passOn(1.95);

        $course = Course::create([
            'title' => 'Data Analytics', 'description' => 'x',
            'form_price' => 50, 'tuition_full' => 1000,
        ]);

        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id,
            'name' => 'Ama', 'email' => 'ama@example.com', 'phone' => '+233240000000',
            'amount' => 50, 'reference' => 'CRS-TUI-TEST', 'status' => 'paid',
            'tuition_reference' => 'TUI-TEST', 'tuition_status' => 'pending',
        ]);

        $charged = PaystackFees::gross(1000.0);

        // What Paystack reports is the grossed figure; only the tuition part
        // may move the balance, or a fully-paid student reads as overpaid.
        $net = PaystackFees::netFrom($charged);

        $this->assertEqualsWithDelta(1000.0, $net, 0.01);
        $this->assertGreaterThan(1000.0, $charged);

        $enrollment->update([
            'tuition_amount' => $net,
            'tuition_fee' => round($charged - $net, 2),
            'tuition_status' => 'paid',
        ]);

        $this->assertEqualsWithDelta(0, $enrollment->fresh()->tuitionBalance(), 0.01);
    }

    public function test_the_breakdown_is_shown_to_the_customer(): void
    {
        $this->passOn(1.95);

        $course = Course::create(['title' => 'Data Analytics', 'description' => 'x', 'form_price' => 50]);

        $this->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Payment charge')
            ->assertSee('1.95%')
            ->assertSee('Total to pay')
            // The button quotes what will actually be taken, not the bare fee.
            ->assertSee('Pay GHS 51');
    }

    public function test_no_breakdown_appears_when_the_charge_is_absorbed(): void
    {
        config(['services.paystack.fee.pass_on' => false]);

        $course = Course::create(['title' => 'Data Analytics', 'description' => 'x', 'form_price' => 50]);

        $this->get(route('courses.show', $course))
            ->assertOk()
            ->assertDontSee('Payment charge')
            ->assertDontSee('Total to pay')
            ->assertSee('Pay GHS 50');
    }
}
