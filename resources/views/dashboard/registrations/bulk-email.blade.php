@extends('layouts.admin')

@section('title', 'Email '.$registrations->count().' registrants — Applyd Academy')

@section('content')
@php
    // Re-indexed so the <option> value (the loop index) lines up with the JS array.
    $prefillJson = collect($templates)
        ->map(fn ($t) => collect($t)->only(['subject', 'heading', 'body', 'cta_label', 'cta_url']))
        ->values()
        ->toJson();
@endphp

<div class="page-head">
    <div>
        <h1 class="section-title">Send email to <span data-recipient-count>{{ $sendable->count() }}</span> {{ Str::plural('registrant', $sendable->count()) }}</h1>
        <p style="color: var(--ink-soft);">Written once, sent individually — each person gets their own copy with their own details filled in.</p>
    </div>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.registrations') }}">Cancel</a>
</div>

@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="error-box">Please fix the highlighted fields.</div>
@endif

<div class="email-editor-grid">
    <div>
        <form method="POST" action="{{ route('dashboard.registrations.bulk-email.send') }}"
              data-confirm="Send this email to {{ $registrations->count() }} {{ Str::plural('person', $registrations->count()) }}? This cannot be undone.">
            @csrf

            <div class="card cms-section">
                <div class="card-head">
                    <div>
                        <h3>Message</h3>
                    </div>
                    @if ($templates)
                        <select id="prefill" data-no-select2 style="max-width:260px;">
                            <option value="">Start from a template…</option>
                            @foreach ($templates as $template)
                                <option value="{{ $loop->index }}">{{ $template['label'] }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="cms-fields">
                    <div class="cms-field span-2">
                        <label class="field-label" for="subject">Subject line</label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required>
                        @error('subject') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="cms-field span-2">
                        <label class="field-label" for="heading">Banner heading <span style="font-weight:400; color:var(--ink-soft);">(the white text on the dark header — leave empty to hide)</span></label>
                        <input type="text" id="heading" name="heading" value="{{ old('heading') }}">
                        @error('heading') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="cms-field span-2">
                        <label class="field-label" for="body">Body</label>
                        <textarea id="body" name="body" rows="12" data-rich>{{ old('body') }}</textarea>
                        @error('body') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="card cms-section">
                <div class="card-head"><div><h3>Button (optional)</h3></div></div>

                <div class="cms-fields">
                    <div class="cms-field">
                        <label class="field-label" for="cta_label">Button text</label>
                        <input type="text" id="cta_label" name="cta_label" value="{{ old('cta_label') }}">
                        @error('cta_label') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="cms-field">
                        <label class="field-label" for="cta_url">Button link</label>
                        <input type="text" id="cta_url" name="cta_url" value="{{ old('cta_url') }}">
                        @error('cta_url') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="cms-field span-2" style="color:var(--ink-soft); font-size:.88rem;">
                        The button only shows when both fields are filled in.
                    </div>
                </div>
            </div>

            @if ($excluded->isNotEmpty())
                <div class="card cms-section">
                    <div class="card-head"><div><h3>Excluded: {{ $excluded->count() }} opted out</h3></div></div>
                    <p style="color:var(--ink-soft); font-size:.88rem; margin:0 0 12px;">
                        {{ $excluded->count() }} of the {{ $registrations->count() }} you picked did not opt in to marketing,
                        so {{ $excluded->count() === 1 ? 'they are' : 'they are' }} excluded and will not receive this.
                    </p>
                    <label class="check-line" style="display:flex; gap:10px; align-items:flex-start; cursor:pointer;">
                        <input type="checkbox" name="service_message" value="1" data-no-select2
                               data-include-count="{{ $registrations->count() }}"
                               {{ old('service_message') ? 'checked' : '' }}>
                        <span>
                            <strong>This is a service message, not marketing</strong> — include the {{ $excluded->count() }}
                            who opted out.
                        </span>
                    </label>
                    <p style="color:var(--ink-soft); font-size:.88rem; margin:8px 0 0;">
                        Only tick this for mail they need regardless of consent: a schedule change, a joining link,
                        a cancellation. Never for promotions.
                    </p>
                </div>
            @endif

            <div style="position:sticky; bottom:0; padding:16px 0; background:linear-gradient(to top, var(--bg-soft) 60%, transparent); display:flex; gap:10px; align-items:center;">
                <button type="submit" class="btn btn-brand" @disabled($sendable->isEmpty() && $excluded->isNotEmpty())>
                    Send to <span data-recipient-count>{{ $sendable->count() }}</span> {{ Str::plural('recipient', $sendable->count()) }}
                </button>
                <a class="btn btn-outline" href="{{ route('dashboard.registrations') }}">Cancel</a>
                @if ($sendable->isEmpty())
                    <span style="color:var(--ink-soft); font-size:.85rem;">Nobody in this selection has opted in.</span>
                @endif
            </div>
        </form>
    </div>

    <aside class="email-editor-side">
        <div class="card">
            <h3 style="margin-bottom:10px;">Receiving this ({{ $sendable->count() }})</h3>
            @if ($sendable->isEmpty())
                <p style="color:var(--ink-soft); font-size:.88rem; margin:0;">Nobody — every registrant you picked has opted out.</p>
            @else
                <ul class="placeholder-list" style="max-height:240px; overflow-y:auto;">
                    @foreach ($sendable as $registration)
                        <li><code>{{ $registration->email }}</code><span>{{ $registration->full_name }}</span></li>
                    @endforeach
                </ul>
            @endif
        </div>

        @if ($excluded->isNotEmpty())
            <div class="card">
                <h3 style="margin-bottom:10px;">Excluded — opted out ({{ $excluded->count() }})</h3>
                <ul class="placeholder-list" style="max-height:200px; overflow-y:auto; opacity:.7;">
                    @foreach ($excluded as $registration)
                        <li>
                            <code>{{ $registration->email }}</code>
                            <span>{{ $registration->full_name }} <span class="badge badge-no">No opt-in</span></span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <h3 style="margin-bottom:10px;">Placeholders</h3>
            <p style="color:var(--ink-soft); font-size:.88rem; margin-bottom:12px;">Paste any of these into the subject, heading, body or button link. They are replaced with each recipient's own details as the email goes out.</p>
            <ul class="placeholder-list">
                @foreach ($placeholders as $token => $meaning)
                    @php $printed = sprintf('{%s %s %s}', '{', $token, '}'); @endphp
                    <li><code>{{ $printed }}</code><span>{{ $meaning }}</span></li>
                @endforeach
            </ul>
        </div>

        <div class="card">
            <h3 style="margin-bottom:10px;">Before you send</h3>
            <p style="color:var(--ink-soft); font-size:.88rem; margin:0;">
                Every send is recorded on <a href="{{ route('dashboard.email-logs') }}">Email Delivery</a>, so you can
                see exactly who received it and retry anyone who bounced. There is no unsend.
            </p>
        </div>
    </aside>
</div>

@include('partials.quill')

@push('scripts')
<script>
// Keep the headline and button count in step with the service-message override,
// so the number on the button is always the number of people who get mail.
(function () {
    var toggle = document.querySelector('input[name="service_message"]');
    if (!toggle) return;

    var sendable = {{ $sendable->count() }};
    var withExcluded = parseInt(toggle.dataset.includeCount, 10);
    var submit = toggle.form.querySelector('button[type="submit"]');

    function sync() {
        var n = toggle.checked ? withExcluded : sendable;
        document.querySelectorAll('[data-recipient-count]').forEach(function (el) { el.textContent = n; });
        if (submit) submit.disabled = n === 0;
        toggle.form.setAttribute('data-confirm',
            'Send this email to ' + n + (n === 1 ? ' person' : ' people') + '? This cannot be undone.');
    }

    toggle.addEventListener('change', sync);
    sync();
})();

(function () {
    var prefill = document.getElementById('prefill');
    if (!prefill) return;

    var TEMPLATES = {!! $prefillJson !!};

    prefill.addEventListener('change', function () {
        var tpl = TEMPLATES[this.value];
        if (!tpl) return;

        document.getElementById('subject').value = tpl.subject || '';
        document.getElementById('heading').value = tpl.heading || '';
        document.getElementById('cta_label').value = tpl.cta_label || '';
        document.getElementById('cta_url').value = tpl.cta_url || '';

        // Quill owns the textarea's visible content, so write through the editor.
        var body = document.getElementById('body');
        var holder = body.parentNode.querySelector('.rich-editor-holder');
        var quill = holder && window.Quill ? Quill.find(holder) : null;

        if (quill) {
            quill.setText('');
            quill.clipboard.dangerouslyPasteHTML(tpl.body || '');
        } else {
            body.value = tpl.body || '';
        }
    });
})();
</script>
@endpush
@endsection
