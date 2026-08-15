@extends('layouts.admin')

@section('title', $questionnaire->title.' — Questionnaires')

@section('content')
<div class="page-head">
    <h1 class="section-title">{{ $questionnaire->title }}</h1>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open
                data-modal-url="{{ route('dashboard.questionnaires.questions.create', $questionnaire) }}"
                data-modal-title="Add Question">Add Question</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.questionnaires.responses', $questionnaire) }}">Responses ({{ number_format($responseCount) }})</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.questionnaires.edit', $questionnaire) }}"
           data-modal-open data-modal-url="{{ route('dashboard.questionnaires.edit', $questionnaire) }}" data-modal-title="Edit Form">Form Settings</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.questionnaires') }}">All Forms</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

<div class="stat-cards">
    <div class="stat-card sv-flat">
        <div class="num">{{ number_format($liveCount) }}</div>
        <div class="lbl">Live Questions</div>
        <div class="stat-meta">{{ number_format($totalCount) }} in total</div>
    </div>
    <div class="stat-card sv-flat">
        <div class="num">{{ number_format($responseCount) }}</div>
        <div class="lbl">Responses</div>
        <div class="stat-meta"><a href="{{ route('dashboard.questionnaires.responses', $questionnaire) }}">See them</a></div>
    </div>
    <div class="stat-card sv-flat">
        <div class="num">{{ $questionnaire->is_published ? ($questionnaire->isOpen() ? 'Open' : 'Closed') : 'Draft' }}</div>
        <div class="lbl">Status</div>
        <div class="stat-meta">
            @if ($reason = $questionnaire->closedReason())
                {{ $reason }}
            @else
                Accepting responses
            @endif
        </div>
    </div>
</div>

{{-- Sharing is a once-per-form job, so it stays folded away. --}}
<details class="card sv-flat sv-disclose" style="margin-bottom: 22px;" @if ($questionnaire->is_published) open @endif>
    <summary>
        <span class="sv-disclose-title">Share this form</span>
        <span class="sv-disclose-hint">{{ $questionnaire->publicUrl() }}</span>
        <svg class="sv-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
    </summary>

    <div class="sv-share">
        <div class="sv-share-qr" id="formQrBox">
            <canvas id="formQr" width="168" height="168"></canvas>
            <div class="sv-qr-fallback" hidden>
                <strong>QR unavailable</strong>
                <span>Share the link instead.</span>
            </div>
        </div>
        <div class="sv-share-body">
            @unless ($questionnaire->is_published)
                <p><strong>This form is still a draft</strong> — the link 404s until you publish it in Form Settings.</p>
            @else
                <p>Send the link, or put the code on a screen — anyone who has it can fill the form in. No account needed.</p>
            @endunless
            <div class="sv-share-url" id="formUrl">{{ $questionnaire->publicUrl() }}</div>
            <div class="sv-share-actions">
                <button type="button" class="btn btn-sm btn-brand" id="formCopy">Copy link</button>
                @if ($questionnaire->is_published)
                    <a class="btn btn-sm btn-outline" href="{{ $questionnaire->publicUrl() }}" target="_blank" rel="noopener">Open form</a>
                @endif
                <button type="button" class="btn btn-sm btn-outline" id="formQrDownload">Download QR</button>
            </div>
        </div>
    </div>
</details>

<div class="card">
    <p style="color:var(--ink-soft); margin:0 0 14px;">
        Drag a row to change the order people answer in. Deleting a question keeps the answers already collected for it.
    </p>
    <livewire:questionnaire-questions-table :questionnaire-id="$questionnaire->id" :key="'questions-'.$questionnaire->id" />
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
<script>
(function () {
    var url = @json($questionnaire->publicUrl());

    // QR is a nicety — if the CDN is blocked the link still does the job.
    var canvas = document.getElementById('formQr');
    var box = document.getElementById('formQrBox');
    if (canvas && window.QRious) {
        new QRious({ element: canvas, value: url, size: 168, level: 'M', background: '#ffffff', foreground: '#272827' });
    } else if (box) {
        canvas.hidden = true;
        var fallback = box.querySelector('.sv-qr-fallback');
        if (fallback) fallback.hidden = false;
    }

    var copy = document.getElementById('formCopy');
    if (copy) {
        copy.addEventListener('click', function () {
            navigator.clipboard.writeText(url).then(function () {
                copy.textContent = 'Copied';
                setTimeout(function () { copy.textContent = 'Copy link'; }, 1800);
            });
        });
    }

    var download = document.getElementById('formQrDownload');
    if (download && canvas) {
        download.addEventListener('click', function () {
            var a = document.createElement('a');
            a.href = canvas.toDataURL('image/png');
            a.download = @json($questionnaire->slug).'-qr.png';
            a.click();
        });
    }
})();
</script>
<script>
(function () {
    // The question form reshapes itself around the answer type. This lives on
    // the page, not in the partial: the shared modal injects forms with
    // innerHTML, which never runs an inline <script>.
    var OPTION_TYPES = ['radio', 'checkbox', 'select'];
    var PLACEHOLDER_TYPES = ['short_text', 'long_text', 'number', 'email', 'phone'];

    var modalBody = document.getElementById('adminModalBody');
    if (!modalBody) return;

    function toggle(nodes, show) {
        Array.prototype.forEach.call(nodes, function (node) { node.hidden = !show; });
    }

    function sync(form) {
        var typeSelect = form.querySelector('[data-question-type]');
        if (!typeSelect) return;

        var type = typeSelect.value;

        toggle(form.querySelectorAll('[data-options-field]'), OPTION_TYPES.indexOf(type) !== -1);
        toggle(form.querySelectorAll('[data-max-select-field]'), type === 'checkbox');
        toggle(form.querySelectorAll('[data-placeholder-field]'), PLACEHOLDER_TYPES.indexOf(type) !== -1);
        toggle(form.querySelectorAll('[data-file-field]'), type === 'file');
    }

    function syncAll() {
        modalBody.querySelectorAll('form').forEach(sync);
    }

    // Select2 fires its change through jQuery, which a native listener on the
    // hidden original still receives — but only via delegation on the document.
    document.addEventListener('change', function (e) {
        if (e.target.matches('[data-question-type]')) sync(e.target.closest('form'));
    });
    if (window.jQuery) {
        jQuery(document).on('change', '[data-question-type]', function () { sync(this.closest('form')); });
    }

    new MutationObserver(syncAll).observe(modalBody, { childList: true });
})();
</script>
@endpush
