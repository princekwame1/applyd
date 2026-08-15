@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.questionnaires.update', $model) : route('dashboard.questionnaires.store') }}"
      data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div class="span-2">
            <label class="field-label" for="f_title">What's this form called? <span class="req">*</span></label>
            <input type="text" id="f_title" name="title" value="{{ old('title', $model?->title) }}"
                   placeholder="e.g. Digital Skills Training — Sign-up" required>
            <div class="upload-hint">People see this as the heading at the top of the form.</div>
            <div class="field-error" data-error="title">@error('title'){{ $message }}@enderror</div>
        </div>

        <div class="span-2">
            <label class="field-label" for="f_slug">Its web address</label>
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="color:var(--ink-soft); font-size:.86rem; white-space:nowrap;">{{ url('/forms') }}/</span>
                <input type="text" id="f_slug" name="slug" value="{{ old('slug', $model?->slug) }}"
                       placeholder="leave this empty and we'll make one from the name" style="flex:1;">
            </div>
            <div class="upload-hint">
                This is the link you'll be sharing, so keep it short — lower-case letters, numbers and dashes.
                @if ($isEdit)<strong>Change it and any link or QR code already out there stops working.</strong>@endif
            </div>
            <div class="field-error" data-error="slug">@error('slug'){{ $message }}@enderror</div>
        </div>

        <div class="span-2">
            <label class="field-label" for="f_description">A short intro</label>
            <textarea id="f_description" name="description" rows="3" style="width:100%; font-family:inherit;"
                      placeholder="e.g. A few quick questions so we can pitch the training at the right level. Takes about three minutes.">{{ old('description', $model?->description) }}</textarea>
            <div class="upload-hint">Optional. Sits under the heading — a good place to say why you're asking and how long it takes.</div>
            <div class="field-error" data-error="description"></div>
        </div>

        <div class="span-2">
            <label class="field-label" for="f_success">What should they see afterwards?</label>
            <textarea id="f_success" name="success_message" rows="2" style="width:100%; font-family:inherit;"
                      placeholder="e.g. Thanks — we've got your answers and we'll be in touch before the session.">{{ old('success_message', $model?->success_message) }}</textarea>
            <div class="upload-hint">Shown on the thank-you page once they've submitted.</div>
            <div class="field-error" data-error="success_message"></div>
        </div>

        <div>
            <label class="field-label" for="f_submit_label">Wording on the button</label>
            <input type="text" id="f_submit_label" name="submit_label" value="{{ old('submit_label', $model?->submit_label) }}"
                   placeholder="Submit">
            <div class="upload-hint">Optional — try "Send my answers" or "Sign me up".</div>
            <div class="field-error" data-error="submit_label"></div>
        </div>

        <div>
            <label class="field-label">Is it live?</label>
            <label class="switch-row">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $isEdit ? $model->is_published : false))>
                <span>Yes, the link works <small>(off = a draft only you can see)</small></span>
            </label>
        </div>

        <div>
            <label class="field-label" for="f_opens_at">Start taking answers</label>
            <input type="datetime-local" id="f_opens_at" name="opens_at"
                   value="{{ old('opens_at', $model?->opens_at?->format('Y-m-d\TH:i')) }}">
            <div class="upload-hint">Optional. Before then, visitors are told when it opens.</div>
            <div class="field-error" data-error="opens_at"></div>
        </div>

        <div>
            <label class="field-label" for="f_closes_at">Stop taking answers</label>
            <input type="datetime-local" id="f_closes_at" name="closes_at"
                   value="{{ old('closes_at', $model?->closes_at?->format('Y-m-d\TH:i')) }}">
            <div class="upload-hint">Optional. After then, the link stays up but politely closes.</div>
            <div class="field-error" data-error="closes_at">@error('closes_at'){{ $message }}@enderror</div>
        </div>

        <div class="span-2">
            <label class="field-label" for="f_response_limit">Stop after how many replies?</label>
            <input type="number" id="f_response_limit" name="response_limit" min="1"
                   value="{{ old('response_limit', $model?->response_limit) }}" placeholder="No limit — keep taking them">
            <div class="upload-hint">Handy when there are only so many seats. The form closes itself once it's full.</div>
            <div class="field-error" data-error="response_limit"></div>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save changes' : 'Create form' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
