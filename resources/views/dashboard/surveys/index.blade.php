@extends('layouts.admin')

@section('title', 'Pulse Check — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Pulse Check</h1>
    <div style="display:flex; gap:10px;">
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.surveys.manage') }}">Manage Surveys</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.surveys.questions') }}">Manage Questions</a>
        @if ($survey)
            <a class="btn btn-sm btn-outline" href="{{ route('dashboard.surveys.export', ['survey' => $survey->slug]) }}">Export Excel</a>
        @endif
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

@if (! $survey)
    <div class="card sv-empty-state">
        <h2 style="margin-bottom:8px;">No surveys yet</h2>
        <p style="color:var(--ink-soft); margin-bottom:16px;">
            Create a check-in form, add its questions, and share the link or QR with the room.
        </p>
        <a class="btn btn-brand btn-sm" href="{{ route('dashboard.surveys.manage') }}">Create a survey</a>
    </div>
@else

<div class="sv-tabs">
    @foreach ($surveys as $tab)
        <a class="sv-tab {{ $tab->id === $survey->id ? 'active' : '' }}"
           href="{{ route('dashboard.surveys', ['survey' => $tab->slug]) }}">
            {{ $tab->name }} <b>({{ number_format($tab->responses_count) }})</b>
            @unless ($tab->is_active)<span class="sv-tab-closed" title="Closed to new answers">closed</span>@endunless
        </a>
    @endforeach
</div>

{{-- The glance: three numbers. Everything else is a click away. --}}
<div class="stat-cards">
    <div class="stat-card sv-flat">
        <div class="num">{{ number_format($total) }}</div>
        <div class="lbl">Responses — {{ $survey->name }}</div>
    </div>
    <div class="stat-card sv-flat">
        <div class="num">{{ number_format($today) }}</div>
        <div class="lbl">Submitted Today</div>
    </div>
    <div class="stat-card sv-flat">
        <div class="num">{{ count($summary) }}</div>
        <div class="lbl">Live Questions</div>
        <div class="stat-meta">
            @if ($survey->is_active)
                <a href="{{ route('surveys.show', $survey) }}" target="_blank" rel="noopener">Open the public form</a>
            @else
                Closed — not accepting answers
            @endif
        </div>
    </div>
</div>

{{-- Sharing is a once-per-session job, so it stays folded away. --}}
<details class="card sv-flat sv-disclose" style="margin-bottom: 22px;">
    <summary>
        <span class="sv-disclose-title">Share this check-in</span>
        <span class="sv-disclose-hint">{{ route('surveys.show', $survey) }}</span>
        <svg class="sv-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
    </summary>

    <div class="sv-share">
        <div class="sv-share-qr" id="surveyQrBox">
            <canvas id="surveyQr" width="168" height="168"></canvas>
            <div class="sv-qr-fallback" hidden>
                <strong>QR unavailable</strong>
                <span>Share the link instead.</span>
            </div>
        </div>
        <div class="sv-share-body">
            <p>Put the code on the screen or print it for the wall — participants scan it and answer on their phones. No account needed.</p>
            <div class="sv-share-url" id="surveyUrl">{{ route('surveys.show', $survey) }}</div>
            <div class="sv-share-actions">
                <button type="button" class="btn btn-sm btn-brand" id="surveyCopy">Copy link</button>
                <a class="btn btn-sm btn-outline" href="{{ route('dashboard.surveys.poster', ['survey' => $survey->slug]) }}" target="_blank" rel="noopener">Print poster</a>
                <button type="button" class="btn btn-sm btn-outline" id="surveyQrDownload">Download QR</button>
            </div>
        </div>
    </div>
</details>

<div class="card sv-flat" style="margin-bottom: 22px;">
    <div class="card-head">
        <h2>Results</h2>
        @if (count($summary) && $total)
            <button type="button" class="sv-expand-all" id="svExpandAll" data-expanded="0">Expand all</button>
        @endif
    </div>

    @if (! count($summary))
        <p class="sv-empty">This survey has no live questions yet. <a href="{{ route('dashboard.surveys.questions') }}">Add some</a>.</p>
    @elseif (! $total)
        <p class="sv-empty">No responses yet.</p>
    @else
        {{-- One row per question: the headline figure reads without opening
             anything, the full distribution is behind the toggle. --}}
        <div class="sv-questions">
            @foreach ($summary as $item)
                <details class="sv-q">
                    <summary>
                        <svg class="sv-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                        <span class="sv-q-head">
                            <span class="sv-q-title">{{ $item['question']->prompt }}</span>
                            <span class="sv-q-meta">{{ $item['answered'] }} of {{ $total }} answered</span>
                        </span>
                        <span class="sv-q-headline">
                            @if ($item['question']->type === 'text')
                                {{ count($item['texts']) }} written {{ Str::plural('answer', count($item['texts'])) }}
                            @elseif ($item['average'] !== null)
                                average {{ $item['average'] }} of {{ count($item['buckets']) }}
                            @elseif ($item['top'])
                                <b>{{ $item['top']['label'] }}</b> {{ $item['top']['percent'] }}%
                            @else
                                —
                            @endif
                        </span>
                    </summary>

                    <div class="sv-q-body">
                        @if ($item['question']->type === 'text')
                            @if (! $item['texts'])
                                <p class="sv-empty">No answers yet.</p>
                            @else
                                <div class="sv-quotes">
                                    @foreach (array_slice($item['texts'], 0, 12) as $text)
                                        <div class="sv-quote">{{ $text }}</div>
                                    @endforeach
                                    @if (count($item['texts']) > 12)
                                        <div class="sv-more">+{{ count($item['texts']) - 12 }} more — see the full list below or export.</div>
                                    @endif
                                </div>
                            @endif
                        @else
                            @foreach ($item['buckets'] as $bucket)
                                <div class="sv-bar-row">
                                    <span class="sv-bar-label">{{ $bucket['label'] }}</span>
                                    <span class="sv-bar"><span class="sv-bar-fill" style="width: {{ $bucket['percent'] }}%;"></span></span>
                                    <span class="sv-bar-count">{{ $bucket['count'] }} · {{ $bucket['percent'] }}%</span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    @endif
</div>

{{-- The raw table is the deepest level of detail, so it starts closed too. --}}
<details class="card sv-flat sv-disclose">
    <summary>
        <span class="sv-disclose-title">Every response</span>
        <span class="sv-disclose-hint">{{ number_format($total) }} {{ Str::plural('submission', $total) }}</span>
        <svg class="sv-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
    </summary>
    <livewire:survey-responses-table :survey-id="$survey->id" :key="'responses-'.$survey->id" />
</details>
@endif
@endsection

@if ($survey)
@push('scripts')
<script src="{{ asset('js/qrcode.js') }}"></script>
<script src="{{ asset('js/qr-share.js') }}"></script>
<script>
(function () {
    var canvas = document.getElementById('surveyQr');
    var box = document.getElementById('surveyQrBox');
    var download = document.getElementById('surveyQrDownload');
    var url = @json(route('surveys.show', $survey));
    if (!canvas) return;

    // If the generator can't load at all, the frame stays on the page in its
    // unavailable state rather than collapsing — the panel still has to read
    // as "the QR goes here".
    var drawn = window.ApplydQr && ApplydQr.draw(canvas, url, 168);

    if (!drawn) {
        box.classList.add('is-missing');
        canvas.hidden = true;
        box.querySelector('.sv-qr-fallback').hidden = false;
        download.disabled = true;
        download.title = 'The QR generator could not load';
    }

    download.addEventListener('click', function () {
        if (!drawn) return;
        var link = document.createElement('a');
        link.download = 'pulse-check-{{ $survey->slug }}-qr.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });

    // One switch for the whole results list, for when you do want it all open.
    var expandAll = document.getElementById('svExpandAll');
    if (expandAll) {
        expandAll.addEventListener('click', function () {
            var open = expandAll.getAttribute('data-expanded') !== '1';
            document.querySelectorAll('.sv-q').forEach(function (row) { row.open = open; });
            expandAll.setAttribute('data-expanded', open ? '1' : '0');
            expandAll.textContent = open ? 'Collapse all' : 'Expand all';
        });
    }

    document.getElementById('surveyCopy').addEventListener('click', function () {
        var done = function () {
            Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2200, icon: 'success', title: 'Link copied' });
        };

        ApplydQr.copy(url).then(done);
    });
})();
</script>
@endpush
@endif
