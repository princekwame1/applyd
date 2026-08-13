@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.videos.update', $model) : route('dashboard.videos.store') }}"
      enctype="multipart/form-data" data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div class="span-2">
            <label class="field-label" for="v_title">Title <span class="req">*</span></label>
            <input type="text" id="v_title" name="title" value="{{ old('title', $model?->title) }}" placeholder="e.g. Week 3 — Building your first automation" required>
            <div class="field-error" data-error="title">@error('title'){{ $message }}@enderror</div>
        </div>
        <div class="span-2">
            <label class="field-label" for="v_url">YouTube Link <span class="req">*</span></label>
            <input type="text" id="v_url" name="youtube_url"
                   value="{{ old('youtube_url', $isEdit ? $model->watch_url : '') }}"
                   placeholder="https://www.youtube.com/watch?v=…" required>
            <div class="upload-hint">Paste the watch, share or embed link — youtu.be and Shorts links work too.</div>
            <div class="field-error" data-error="youtube_url">@error('youtube_url'){{ $message }}@enderror</div>
        </div>
        <div>
            <label class="field-label" for="v_session">Session</label>
            <input type="text" id="v_session" name="session_label" value="{{ old('session_label', $model?->session_label) }}" placeholder="e.g. Cohort 1 · Week 3">
            <div class="field-error" data-error="session_label"></div>
        </div>
        <div>
            <label class="field-label" for="v_recorded">Recorded On</label>
            <input type="date" id="v_recorded" name="recorded_on" value="{{ old('recorded_on', $model?->recorded_on?->format('Y-m-d')) }}">
            <div class="field-error" data-error="recorded_on"></div>
        </div>
        <div class="span-2">
            <label class="field-label" for="v_description">Description</label>
            <textarea id="v_description" name="description" rows="3" placeholder="What this session covered (optional)" style="width:100%; font-family:inherit;">{{ old('description', $model?->description) }}</textarea>
            <div class="field-error" data-error="description"></div>
        </div>
        <div class="span-2">
            <label class="field-label" for="v_thumbnail">Custom Thumbnail</label>
            <div class="file-field">
                <span class="file-preview" data-preview-for="v_thumbnail">
                    @if ($isEdit)
                        <img src="{{ $model->thumbnail_url }}" alt="">
                    @else
                        <i class="fa-regular fa-image"></i>
                    @endif
                </span>
                <label class="file-btn" for="v_thumbnail"><i class="fa-solid fa-upload"></i> Choose image</label>
                <input type="file" id="v_thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/webp" data-preview="v_thumbnail" data-max-kb="2048" hidden>
                <span class="file-name" data-filename-for="v_thumbnail"></span>
            </div>
            <div class="upload-hint">Optional — leave empty to use YouTube's own cover image.</div>
            <div class="field-error" data-error="thumbnail"></div>
        </div>
        <div class="span-2">
            <label class="switch-row">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $isEdit ? $model->is_published : true))>
                <span>Published <small>(uncheck to hide it from the website)</small></span>
            </label>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Add Video' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
