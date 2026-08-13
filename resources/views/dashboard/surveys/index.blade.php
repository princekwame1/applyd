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

<div class="stat-cards">
    <div class="stat-card">
        <span class="stat-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11H5a2 2 0 0 0-2 2v7h6z"/><path d="M15 4h-6v16h6z"/><path d="M21 8h-6v12h6z"/></svg>
        </span>
        <div class="num">{{ number_format($total) }}</div>
        <div class="lbl">Responses — {{ $survey->name }}</div>
    </div>
    <div class="stat-card">
        <span class="stat-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </span>
        <div class="num">{{ number_format($today) }}</div>
        <div class="lbl">Submitted Today</div>
    </div>
    <div class="stat-card">
        <span class="stat-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </span>
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

<div class="card sv-share">
    <div class="sv-share-qr" id="surveyQrBox">
        <canvas id="surveyQr" width="168" height="168"></canvas>
        <div class="sv-qr-fallback" hidden>
            <strong>QR unavailable</strong>
            <span>Share the link instead.</span>
        </div>
    </div>
    <div class="sv-share-body">
        <h2>Share this check-in</h2>
        <p>Put the code on the screen or print it for the wall — participants scan it and answer on their phones. No account needed.</p>
        <div class="sv-share-url" id="surveyUrl">{{ route('surveys.show', $survey) }}</div>
        <div class="sv-share-actions">
            <button type="button" class="btn btn-sm btn-brand" id="surveyCopy">Copy link</button>
            <a class="btn btn-sm btn-outline" href="{{ route('dashboard.surveys.poster', ['survey' => $survey->slug]) }}" target="_blank" rel="noopener">Print poster</a>
            <button type="button" class="btn btn-sm btn-outline" id="surveyQrDownload">Download QR</button>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 22px;">
    <div class="card-head"><h2>Results</h2></div>

    @if (! count($summary))
        <p class="sv-empty">This survey has no live questions yet. <a href="{{ route('dashboard.surveys.questions') }}">Add some</a>.</p>
    @elseif (! $total)
        <p class="sv-empty">No responses yet.</p>
    @else
        <div class="sv-questions">
            @foreach ($summary as $item)
                <div>
                    <div class="sv-q-title">{{ $item['question']->prompt }}</div>
                    <div class="sv-q-meta">
                        {{ $item['answered'] }} of {{ $total }} answered
                        @if ($item['average'] !== null)
                            · average {{ $item['average'] }} of {{ count($item['buckets']) }}
                        @endif
                    </div>

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
            @endforeach
        </div>
    @endif
</div>

<div class="card">
    <div class="card-head"><h2>Every response</h2></div>
    <livewire:survey-responses-table :survey-id="$survey->id" :key="'responses-'.$survey->id" />
</div>
@endif
@endsection

@if ($survey)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
<script>
(function () {
    var canvas = document.getElementById('surveyQr');
    var box = document.getElementById('surveyQrBox');
    var download = document.getElementById('surveyQrDownload');
    var url = @json(route('surveys.show', $survey));
    if (!canvas) return;

    // The QR is drawn by a CDN script. If it's blocked or the machine is offline
    // the frame stays on the page in its unavailable state rather than
    // collapsing — the panel still has to read as "the QR goes here".
    var drawn = false;
    try {
        if (window.QRious) {
            new QRious({ element: canvas, value: url, size: 168, level: 'M', background: '#ffffff', foreground: '#272827' });
            drawn = true;
        }
    } catch (e) { /* fall through to the placeholder */ }

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

    document.getElementById('surveyCopy').addEventListener('click', function () {
        // navigator.clipboard needs a secure context; fall back for plain http.
        var done = function () {
            Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2200, icon: 'success', title: 'Link copied' });
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(done);
            return;
        }

        var tmp = document.createElement('textarea');
        tmp.value = url;
        tmp.style.position = 'fixed';
        tmp.style.opacity = '0';
        document.body.appendChild(tmp);
        tmp.select();
        document.execCommand('copy');
        document.body.removeChild(tmp);
        done();
    });
})();
</script>
@endpush
@endif
