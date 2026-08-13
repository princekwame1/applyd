@extends('layouts.company')

@section('title', 'Plans & Credits — '.$company->name)

@section('content')
<div class="page-head">
    <h1 class="section-title">Plans &amp; Credits</h1>
    <a class="btn btn-sm btn-outline" href="{{ route('company.talent') }}">Back to talent pool</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="stat-cards">
    <div class="stat-card"><div class="num">{{ number_format($creditsLeft) }}</div><div class="lbl">Credits left</div></div>
    <div class="stat-card"><div class="num">{{ number_format($creditsUsed) }}</div><div class="lbl">Credits spent</div></div>
    <div class="stat-card"><div class="num">{{ number_format($creditsBought) }}</div><div class="lbl">Credits bought all time</div></div>
</div>

<p style="color:var(--ink-soft); margin:-6px 0 20px; max-width:640px;">
    One credit opens one candidate's CV and contact details, for good. Credits never expire, and buying again
    simply adds to what you have — that's how you expand a plan. Applications sent to your own job posts are
    always free and don't touch your credits.
</p>

@if (! $paymentsEnabled)
    <div class="error-box">Online payment isn't switched on yet. Contact us and we'll add credits to your account manually.</div>
@endif

<div class="plan-grid">
    @forelse ($plans as $plan)
        <div class="card plan-card {{ $plan->is_featured ? 'is-featured' : '' }}">
            @if ($plan->is_featured)<span class="plan-flag">Most popular</span>@endif
            <h3 class="plan-name">{{ $plan->name }}</h3>
            <div class="plan-price">{{ $plan->price_label }}</div>
            <div class="plan-credits">{{ number_format($plan->cv_credits) }} CV {{ Str::plural('unlock', $plan->cv_credits) }}</div>
            @if ($plan->blurb)<p class="plan-blurb">{{ $plan->blurb }}</p>@endif

            @if ($plan->featureList())
                <ul class="plan-features">
                    @foreach ($plan->featureList() as $feature)
                        <li>{{ $feature }}</li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('company.plans.checkout', $plan) }}">
                @csrf
                <button type="submit" class="btn btn-brand btn-sm" @disabled(! $paymentsEnabled)>
                    {{ $creditsBought ? 'Add these credits' : 'Buy this plan' }}
                </button>
            </form>
        </div>
    @empty
        <div class="card center" style="padding:40px;">
            <h3 style="margin-bottom:8px;">No plans on sale yet</h3>
            <p style="color:var(--ink-soft);">Get in touch and we'll sort you out.</p>
        </div>
    @endforelse
</div>

@if ($purchases->isNotEmpty())
    <div class="card" style="margin-top:26px;">
        <h3 style="margin-bottom:14px;">Your purchases</h3>
        <div class="table-wrap">
            <table class="nice">
                <thead><tr><th>Date</th><th>Plan</th><th>Credits</th><th>Amount</th><th>Reference</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->created_at->format('M j, Y') }}</td>
                            <td>{{ $purchase->plan_name }}</td>
                            <td>{{ number_format($purchase->credits) }}</td>
                            <td>{{ $purchase->amount_label }}</td>
                            <td><code style="font-size:.78rem;">{{ $purchase->reference }}</code></td>
                            <td>
                                @if ($purchase->status === 'paid')
                                    <span class="badge badge-yes">Paid</span>
                                @elseif ($purchase->status === 'failed')
                                    <span class="badge badge-no">Failed</span>
                                @else
                                    <span class="badge badge-no">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
