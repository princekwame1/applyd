<?php

namespace App\Http\Controllers;

use App\Models\PlanPurchase;
use App\Models\RecruiterPlan;
use App\Support\Paystack;
use App\Support\PaystackFees;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Buying CV credits. Every purchase is a one-off: the credits it carries never
 * expire, and buying again simply adds more — that is the "expand your plan"
 * path as well as the first one.
 */
class CompanyPlanController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;

        return view('company.plans', [
            'company' => $company,
            'plans' => RecruiterPlan::active()->ordered()->get(),
            'purchases' => $company->purchases()->latest()->get(),
            'creditsLeft' => $company->creditsLeft(),
            'creditsBought' => $company->creditsBought(),
            'creditsUsed' => $company->creditsUsed(),
            'paymentsEnabled' => Paystack::configured(),
        ]);
    }

    public function checkout(Request $request, RecruiterPlan $plan)
    {
        abort_unless($plan->is_active, 404);

        $company = $request->user()->company;

        if (! Paystack::configured()) {
            return back()->with('error', 'Online payment is not available right now. Please contact us to buy credits.');
        }

        $reference = 'CVP-'.$company->id.'-'.strtoupper(Str::random(10));

        // The row exists before the redirect so a payment can always be traced
        // back, even if the recruiter abandons the Paystack page.
        $price = (float) $plan->price;
        // Grossed up so the plan price still lands in full. `amount` stays the
        // price — it is what the purchase is worth — and the charge sits beside it.
        $charged = PaystackFees::gross($price);

        $purchase = $company->purchases()->create([
            'recruiter_plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'credits' => $plan->cv_credits,
            'amount' => $price,
            'fee' => PaystackFees::fee($price),
            'reference' => $reference,
            'status' => 'pending',
        ]);

        $init = Paystack::initialize([
            'email' => $request->user()->email,
            'amount' => PaystackFees::pesewas($charged),
            'currency' => config('services.paystack.currency', 'GHS'),
            'reference' => $reference,
            'callback_url' => route('company.plans.callback'),
            'metadata' => [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'plan' => $plan->name,
                'credits' => $plan->cv_credits,
                'base_amount' => $price,
            ],
        ]);

        if (empty($init['status']) || empty($init['data']['authorization_url'])) {
            $purchase->update(['status' => 'failed']);

            return back()->with('error', 'We could not start the payment. Please try again.');
        }

        return redirect()->away($init['data']['authorization_url']);
    }

    /**
     * Paystack sends the browser back here. Deliberately outside the auth
     * group: verification must not depend on the session still being alive,
     * and the reference itself says which company gets the credits.
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference', $request->query('trxref'));
        $purchase = PlanPurchase::where('reference', $reference)->first();

        abort_unless($purchase, 404);

        if ($purchase->status !== 'paid') {
            $verify = Paystack::configured() ? Paystack::verify($reference) : ['status' => false];
            $paid = ! empty($verify['status']) && ($verify['data']['status'] ?? null) === 'success';

            $purchase->update($paid
                ? ['status' => 'paid', 'paid_at' => now()]
                : ['status' => 'failed']);
        }

        $paid = $purchase->fresh()->status === 'paid';

        return redirect()->route('company.plans')->with(
            $paid ? 'status' : 'error',
            $paid
                ? $purchase->credits.' CV credits added. They never expire.'
                : 'That payment did not go through, so no credits were added.'
        );
    }
}
