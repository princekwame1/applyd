@extends('layouts.admin')

@section('title', 'Edit '.$template['label'].' — Applyd Academy')

@section('content')
<div class="page-head">
    <div>
        <h1 class="section-title">Edit: {{ $template['label'] }}</h1>
        <p style="color: var(--ink-soft);">{{ $template['description'] }}</p>
    </div>
    <a class="btn btn-sm btn-outline" href="{{ route('dashboard.email-templates') }}">All templates</a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="error-box">Please fix the highlighted fields.</div>
@endif

<div class="email-editor-grid">
    <div>
        <form method="POST" action="{{ route('dashboard.email-templates.update', $key) }}">
            @csrf
            @method('PUT')

            <div class="card cms-section">
                <div class="card-head"><div><h3>Message</h3></div></div>

                <div class="cms-fields">
                    <div class="cms-field span-2">
                        <label class="field-label" for="subject">Subject line</label>
                        <input type="text" id="subject" name="subject" value="{{ old('subject', $template['subject']) }}">
                        @error('subject') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="cms-field span-2">
                        <label class="field-label" for="heading">Banner heading <span style="font-weight:400; color:var(--ink-soft);">(the white text on the dark header — leave empty to hide)</span></label>
                        <input type="text" id="heading" name="heading" value="{{ old('heading', $template['heading']) }}">
                        @error('heading') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="cms-field span-2">
                        <label class="field-label" for="body">Body</label>
                        <textarea id="body" name="body" rows="10" data-rich>{{ old('body', $template['body']) }}</textarea>
                        @error('body') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="card cms-section">
                <div class="card-head"><div><h3>Button (optional)</h3></div></div>

                <div class="cms-fields">
                    <div class="cms-field">
                        <label class="field-label" for="cta_label">Button text</label>
                        <input type="text" id="cta_label" name="cta_label" value="{{ old('cta_label', $template['cta_label']) }}">
                        @error('cta_label') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="cms-field">
                        <label class="field-label" for="cta_url">Button link</label>
                        <input type="text" id="cta_url" name="cta_url" value="{{ old('cta_url', $template['cta_url']) }}">
                        @error('cta_url') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="cms-field span-2" style="color:var(--ink-soft); font-size:.88rem;">
                        The button only shows when both fields are filled in.
                    </div>
                </div>
            </div>

            <div class="card cms-section">
                <div class="card-head"><div><h3>Status</h3></div></div>
                <label class="check-line" style="display:flex; gap:10px; align-items:center; cursor:pointer;">
                    <input type="checkbox" name="enabled" value="1" data-no-select2 {{ old('enabled', $template['enabled']) ? 'checked' : '' }}>
                    <span>Send this email automatically</span>
                </label>
                <p style="color:var(--ink-soft); font-size:.88rem; margin:8px 0 0;">Untick to stop the email going out on new registrations. Manual resends from the dashboard still work.</p>
            </div>

            <div style="position:sticky; bottom:0; padding:16px 0; background:linear-gradient(to top, var(--bg-soft) 60%, transparent); display:flex; gap:10px; align-items:center;">
                <button type="submit" class="btn btn-brand">Save changes</button>
                @if ($template['customised'])
                    <span style="color:var(--ink-soft); font-size:.85rem;">Last edited {{ $template['updated_at']?->diffForHumans() }}</span>
                @endif
            </div>
        </form>

        @if ($template['customised'])
            <form method="POST" action="{{ route('dashboard.email-templates.reset', $key) }}"
                  data-confirm="Reset this template back to the default wording? Your changes will be lost.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline btn-sm">Reset to default copy</button>
            </form>
        @endif
    </div>

    <aside class="email-editor-side">
        <div class="card">
            <h3 style="margin-bottom:10px;">Placeholders</h3>
            <p style="color:var(--ink-soft); font-size:.88rem; margin-bottom:12px;">Paste any of these into the subject, heading, body or button link. They are replaced with the registrant's own details when the email goes out.</p>
            <ul class="placeholder-list">
                @foreach ($placeholders as $token => $meaning)
                    @php $printed = sprintf('{%s %s %s}', '{', $token, '}'); @endphp
                    <li><code>{{ $printed }}</code><span>{{ $meaning }}</span></li>
                @endforeach
            </ul>
        </div>

        <div class="card">
            <h3 style="margin-bottom:10px;">Send a test</h3>
            <form method="POST" action="{{ route('dashboard.email-templates.test', $key) }}" style="display:flex; gap:10px; flex-wrap:wrap;">
                @csrf
                <input type="email" name="test_email" placeholder="you@example.com" required
                       value="{{ old('test_email', auth()->user()->email) }}" style="flex:1 1 180px;">
                <button type="submit" class="btn btn-brand btn-sm">Send test</button>
            </form>
            @error('test_email') <div class="field-error">{{ $message }}</div> @enderror
            <p style="color:var(--ink-soft); font-size:.85rem; margin:10px 0 0;">Uses sample data and sends the currently <strong>saved</strong> version.</p>
        </div>

        <div class="card">
            <h3 style="margin-bottom:10px;">Preview</h3>
            <iframe class="email-preview-frame" src="{{ route('dashboard.email-templates.preview', $key) }}" title="Email preview"></iframe>
            <p style="color:var(--ink-soft); font-size:.85rem; margin:10px 0 0;">Saved version with sample data. Save your changes to refresh it.</p>
        </div>
    </aside>
</div>
@include('partials.quill')
@endsection
