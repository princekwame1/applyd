@extends('layouts.admin')

@section('title', 'Finance — Applyd Academy')

@use('App\Support\Finance')

@section('content')
<div class="page-head">
    <h1 class="section-title">Finance</h1>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#financeCreateTpl" data-modal-title="Record an entry">New Entry</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.finance.export', ['from' => $from, 'to' => $to]) }}">Export Excel</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.finance.categories') }}">Categories</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

{{-- The window everything above the table is reported over. Plain GET, so the
     figures on screen are always the ones the Export button will hand you. --}}
<form method="GET" action="{{ route('dashboard.finance') }}" class="fin-period">
    <div>
        <label class="field-label" for="fin_from">Showing</label>
        <input type="date" id="fin_from" name="from" value="{{ $from }}">
    </div>
    <div>
        <label class="field-label" for="fin_to">to</label>
        <input type="date" id="fin_to" name="to" value="{{ $to }}">
    </div>
    <button type="submit" class="btn btn-sm btn-outline">Apply</button>
    @if ($from !== now()->startOfYear()->toDateString() || $to !== now()->toDateString())
        <a class="fin-period-reset" href="{{ route('dashboard.finance') }}">Back to this year</a>
    @endif
</form>

<div class="stat-cards">
    <div class="stat-card sv-flat">
        <div class="num fin-in">{{ Finance::money($summary['income']) }}</div>
        <div class="lbl">Money In</div>
    </div>
    <div class="stat-card sv-flat">
        <div class="num fin-out">{{ Finance::money($summary['expense']) }}</div>
        <div class="lbl">Money Out</div>
    </div>
    <div class="stat-card sv-flat">
        {{-- The one number that can legitimately be negative, so it says which. --}}
        <div class="num {{ $summary['net'] < 0 ? 'fin-out' : 'fin-in' }}">{{ Finance::money($summary['net'], true) }}</div>
        <div class="lbl">Net {{ $summary['net'] < 0 ? '(short)' : '(ahead)' }}</div>
        <div class="stat-meta">{{ number_format($summary['count']) }} {{ Str::plural('entry', $summary['count']) }} in this period</div>
    </div>
    <div class="stat-card sv-flat">
        <div class="num">{{ Finance::money($allTime['net'], true) }}</div>
        <div class="lbl">Net, All Time</div>
        <div class="stat-meta">{{ number_format($allTime['count']) }} {{ Str::plural('entry', $allTime['count']) }} on the books</div>
    </div>
</div>

{{-- Breakdowns are a "when you ask" thing, so they stay folded away. --}}
@if ($incomeByCategory || $expenseByCategory)
    <details class="card sv-flat sv-disclose" style="margin-bottom:22px;">
        <summary>
            <span class="sv-disclose-title">Where it came from, where it went</span>
            <span class="sv-disclose-hint">{{ count($incomeByCategory) + count($expenseByCategory) }} categories used</span>
            <svg class="sv-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
        </summary>

        <div class="fin-breakdowns">
            @foreach ([['Money in', $incomeByCategory, 'fin-in'], ['Money out', $expenseByCategory, 'fin-out']] as [$heading, $rows, $tone])
                <div class="fin-breakdown">
                    <h3>{{ $heading }}</h3>
                    @forelse ($rows as $row)
                        <div class="fin-bar-row">
                            <div class="fin-bar-head">
                                <span>{{ $row['name'] }}</span>
                                <b class="{{ $tone }}">{{ Finance::money($row['total']) }}</b>
                            </div>
                            <div class="fin-bar"><span class="{{ $tone }}-bg" style="width: {{ max($row['share'], 2) }}%;"></span></div>
                            <div class="fin-bar-share">{{ $row['share'] }}%</div>
                        </div>
                    @empty
                        <p class="sv-empty">Nothing recorded in this period.</p>
                    @endforelse
                </div>
            @endforeach
        </div>
    </details>
@endif

<div class="card">
    {{-- Keyed on the period so changing it re-mounts the table with the new
         window rather than leaving stale rows behind. --}}
    <livewire:finance-transactions-table :from="$from" :to="$to" :key="'fin-'.$from.'-'.$to" />
</div>

<template id="financeCreateTpl">
    @include('dashboard.finance.partials.transaction-form', ['model' => null])
</template>
@endsection

@push('scripts')
<script>
(function () {
    // The category list belongs to one side of the books, so the picker narrows
    // to whichever side the entry is on. Lives here, not in the partial: the
    // shared modal injects forms with innerHTML, which never runs an inline
    // <script>.
    var modalBody = document.getElementById('adminModalBody');
    if (!modalBody) return;

    function sync(form) {
        var typeSelect = form.querySelector('[data-entry-type]');
        var categorySelect = form.querySelector('[data-entry-category]');
        if (!typeSelect || !categorySelect) return;

        var type = typeSelect.value;
        var chosenIsWrongSide = false;

        Array.prototype.forEach.call(categorySelect.options, function (option) {
            if (!option.value) return;             // the "no category" row stays
            var keep = option.dataset.type === type;
            option.hidden = !keep;
            option.disabled = !keep;
            if (!keep && option.selected) chosenIsWrongSide = true;
        });

        // Flipping the entry over would otherwise leave a heading from the
        // other side selected, which the server rejects.
        if (chosenIsWrongSide) categorySelect.value = '';

        var hint = form.querySelector('[data-party-hint]');
        if (hint) hint.textContent = type === 'income' ? 'Who paid you?' : 'Who did you pay?';
    }

    function syncAll() {
        modalBody.querySelectorAll('form').forEach(sync);
    }

    document.addEventListener('change', function (e) {
        if (e.target.matches('[data-entry-type]')) sync(e.target.closest('form'));
    });
    if (window.jQuery) {
        jQuery(document).on('change', '[data-entry-type]', function () { sync(this.closest('form')); });
    }

    new MutationObserver(syncAll).observe(modalBody, { childList: true });
})();
</script>
@endpush
