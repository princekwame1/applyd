@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.questionnaires.update', $model) : route('dashboard.questionnaires.store') }}"
      data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div class="span-2">
            <label class="field-label" for="f_title">Form name <span class="req">*</span></label>
            <input type="text" id="f_title" name="title" value="{{ old('title', $model?->title) }}"
                   placeholder="e.g. Mentor Application 2026" required>
            <div class="upload-hint">Shown as the heading on the public form.</div>
            <div class="field-error" data-error="title">@error('title'){{ $message }}@enderror</div>
        </div>

        <div class="span-2">
            <label class="field-label" for="f_slug">Link</label>
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="color:var(--ink-soft); font-size:.86rem; white-space:nowrap;">{{ url('/forms') }}/</span>
                <input type="text" id="f_slug" name="slug" value="{{ old('slug', $model?->slug) }}"
                       placeholder="leave empty to build it from the name" style="flex:1;">
            </div>
            <div class="upload-hint">
                Lower-case letters, numbers and dashes.
                @if ($isEdit)<strong>Changing this breaks any link already shared.</strong>@endif
            </div>
            <div class="field-error" data-error="slug">@error('slug'){{ $message }}@enderror</div>
        </div>

        <div class="span-2">
            <label class="field-label" for="f_description">Intro</label>
            <textarea id="f_description" name="description" rows="3" style="width:100%; font-family:inherit;"
                      placeholder="A sentence or two under the heading, telling people what this is for.">{{ old('description', $model?->description) }}</textarea>
            <div class="field-error" data-error="description"></div>
        </div>

        <div class="span-2">
            <label class="field-label" for="f_success">Thank-you message</label>
            <textarea id="f_success" name="success_message" rows="2" style="width:100%; font-family:inherit;"
                      placeholder="Shown after they submit.">{{ old('success_message', $model?->success_message) }}</textarea>
            <div class="field-error" data-error="success_message"></div>
        </div>

        <div>
            <label class="field-label" for="f_submit_label">Submit button text</label>
            <input type="text" id="f_submit_label" name="submit_label" value="{{ old('submit_label', $model?->submit_label) }}"
                   placeholder="Submit">
            <div class="field-error" data-error="submit_label"></div>
        </div>

        <div>
            <label class="field-label">Published</label>
            <label class="switch-row">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $isEdit ? $model->is_published : false))>
                <span>Link is live <small>(off = draft, the link 404s)</small></span>
            </label>
        </div>

        <div>
            <label class="field-label" for="f_opens_at">Opens</label>
            <input type="datetime-local" id="f_opens_at" name="opens_at"
                   value="{{ old('opens_at', $model?->opens_at?->format('Y-m-d\TH:i')) }}">
            <div class="upload-hint">Optional. Before this, the link says "opens on…".</div>
            <div class="field-error" data-error="opens_at"></div>
        </div>

        <div>
            <label class="field-label" for="f_closes_at">Closes</label>
            <input type="datetime-local" id="f_closes_at" name="closes_at"
                   value="{{ old('closes_at', $model?->closes_at?->format('Y-m-d\TH:i')) }}">
            <div class="upload-hint">Optional. After this, no more responses.</div>
            <div class="field-error" data-error="closes_at"></div>
        </div>

        <div class="span-2">
            <label class="field-label" for="f_response_limit">Response limit</label>
            <input type="number" id="f_response_limit" name="response_limit" min="1"
                   value="{{ old('response_limit', $model?->response_limit) }}" placeholder="Leave empty for no limit">
            <div class="upload-hint">The form closes itself once this many responses are in.</div>
            <div class="field-error" data-error="response_limit"></div>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Create Form' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
