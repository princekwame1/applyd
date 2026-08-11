@extends('layouts.admin')

@section('title', 'Pulse Check Questions — Applyd Academy')

@section('content')
<div class="page-head">
    <h1 class="section-title">Pulse Check Questions</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-brand btn-sm" data-modal-open data-modal-template="#questionCreateTpl" data-modal-title="Add Question">Add Question</button>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.surveys') }}">Back to Results</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

<div class="card">
    <livewire:survey-questions-table />
</div>

<template id="questionCreateTpl">
    @include('dashboard.surveys.partials.question-form', ['model' => null])
</template>
@endsection

@push('scripts')
<script>
(function () {
    // Options apply to choice/scale, the placeholder only to free text — so the
    // form reshapes itself around the answer type. This lives on the page, not
    // in the partial: the shared modal injects forms with innerHTML, which
    // never runs an inline <script>.
    var modalBody = document.getElementById('adminModalBody');
    if (!modalBody) return;

    function sync(form) {
        var typeSelect = form.querySelector('[data-question-type]');
        if (!typeSelect) return;

        var isText = typeSelect.value === 'text';
        var options = form.querySelector('[data-options-field]');
        var placeholder = form.querySelector('[data-placeholder-field]');
        var hint = form.querySelector('[data-options-hint]');

        if (options) options.hidden = isText;
        if (placeholder) placeholder.hidden = !isText;
        if (hint) {
            hint.textContent = typeSelect.value === 'scale'
                ? 'One per line — these are the labels for 1, 2, 3… in order.'
                : 'One per line. Participants pick exactly one.';
        }
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

    // The modal fills its body after the form is fetched or cloned.
    new MutationObserver(syncAll).observe(modalBody, { childList: true });
})();
</script>
@endpush
