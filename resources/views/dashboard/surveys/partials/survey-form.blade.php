@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.surveys.manage.update', $model) : route('dashboard.surveys.manage.store') }}"
      data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div class="span-2">
            <label class="field-label" for="s_name">Name <span class="req">*</span></label>
            <input type="text" id="s_name" name="name" value="{{ old('name', $model?->name) }}"
                   placeholder="e.g. Week 3 · Before you go" required>
            <div class="upload-hint">Shown as the heading on the public form and as the tab on the results page.</div>
            <div class="field-error" data-error="name">@error('name'){{ $message }}@enderror</div>
        </div>

        <div class="span-2">
            <label class="field-label" for="s_slug">Link</label>
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="color:var(--ink-soft); font-size:.86rem; white-space:nowrap;">{{ url('/check-in') }}/</span>
                <input type="text" id="s_slug" name="slug" value="{{ old('slug', $model?->slug) }}"
                       placeholder="leave empty to build it from the name" style="flex:1;">
            </div>
            <div class="upload-hint">
                Lower-case letters, numbers and dashes.
                @if ($isEdit)<strong>Changing this breaks any QR code or link already handed out.</strong>@endif
            </div>
            <div class="field-error" data-error="slug">@error('slug'){{ $message }}@enderror</div>
        </div>

        <div>
            <label class="field-label" for="s_eyebrow">Eyebrow</label>
            <input type="text" id="s_eyebrow" name="eyebrow" value="{{ old('eyebrow', $model?->eyebrow) }}"
                   placeholder="e.g. 02 · Wrapping up">
            <div class="upload-hint">Small label above the name on the picker card.</div>
            <div class="field-error" data-error="eyebrow"></div>
        </div>

        <div>
            <label class="field-label">Open</label>
            <label class="switch-row">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isEdit ? $model->is_active : true))>
                <span>Accepting answers <small>(switch off to close it — results are kept)</small></span>
            </label>
        </div>

        <div class="span-2">
            <label class="field-label" for="s_blurb">Blurb</label>
            <input type="text" id="s_blurb" name="blurb" value="{{ old('blurb', $model?->blurb) }}"
                   placeholder="e.g. Tell us how the session went.">
            <div class="field-error" data-error="blurb"></div>
        </div>

        <div class="span-2">
            <label class="field-label" for="s_thanks">Thank-you message</label>
            <input type="text" id="s_thanks" name="thanks" value="{{ old('thanks', $model?->thanks) }}"
                   placeholder="Shown after they submit">
            <div class="field-error" data-error="thanks"></div>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Create Survey' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
