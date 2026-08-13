@extends('layouts.admin')

@section('title', 'Plan Purchases — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Plan Purchases</h1>
    <div style="display:flex; gap:10px;">
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.recruiter-plans') }}">Plans</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.plan-purchases.export') }}">Export Excel</a>
    </div>
</div>

<div class="stat-cards">
    <div class="stat-card">
        <div class="num">{{ config('services.paystack.currency', 'GHS') }} {{ number_format((float) $revenue, 2) }}</div>
        <div class="lbl">Paid revenue</div>
    </div>
    <div class="stat-card"><div class="num">{{ number_format($creditsSold) }}</div><div class="lbl">Credits sold</div></div>
    <div class="stat-card"><div class="num">{{ number_format($paidCount) }}</div><div class="lbl">Settled purchases</div></div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

{{-- Bank transfer, an agreed deal, a trial: credits granted here are recorded
     as a settled purchase, so a balance is always one table's sum. --}}
<div class="card" style="margin-bottom:22px;">
    <h3 style="margin-bottom:6px;">Add credits by hand</h3>
    <p style="color:var(--ink-soft); font-size:.9rem; margin-bottom:14px; max-width:600px;">
        For recruiters who paid offline or agreed a deal with you. It lands in the list below like any other purchase.
    </p>
    <form method="POST" action="{{ route('dashboard.plan-purchases.grant') }}" class="form-grid" style="gap:14px 16px; align-items:end;">
        @csrf
        <div>
            <label class="field-label" for="g_company">Company <span class="req">*</span></label>
            <select id="g_company" name="company_id" required>
                <option value="">Choose a company…</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
            @error('company_id') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="g_credits">Credits <span class="req">*</span></label>
            <input type="number" id="g_credits" name="credits" min="1" value="{{ old('credits') }}" placeholder="e.g. 25" required>
            @error('credits') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="g_amount">Amount paid ({{ config('services.paystack.currency', 'GHS') }})</label>
            <input type="number" id="g_amount" name="amount" step="0.01" min="0" value="{{ old('amount') }}" placeholder="0.00">
            @error('amount') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="field-label" for="g_note">Label</label>
            <input type="text" id="g_note" name="note" value="{{ old('note') }}" placeholder="e.g. Growth (bank transfer)">
            @error('note') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <button type="submit" class="btn btn-brand btn-sm">Add credits</button>
        </div>
    </form>
</div>

<div class="card">
    <livewire:plan-purchases-table />
</div>
@endsection
