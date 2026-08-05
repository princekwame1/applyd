@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.courses.update', $model) : route('dashboard.courses.store') }}"
      enctype="multipart/form-data" data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div class="span-2">
            <label class="field-label" for="c_title">Title <span class="req">*</span></label>
            <input type="text" id="c_title" name="title" value="{{ old('title', $model?->title) }}" placeholder="e.g. Certificate in Digital Productivity" required>
            <div class="field-error" data-error="title">@error('title'){{ $message }}@enderror</div>
        </div>
        <div>
            <label class="field-label" for="c_level">Level</label>
            <select id="c_level" name="level">
                <option value="">Select…</option>
                @foreach (\App\Models\Course::LEVELS as $level)
                    <option value="{{ $level }}" @selected(old('level', $model?->level) === $level)>{{ $level }}</option>
                @endforeach
            </select>
            <div class="field-error" data-error="level"></div>
        </div>
        <div>
            <label class="field-label" for="c_duration">Duration</label>
            <input type="text" id="c_duration" name="duration" value="{{ old('duration', $model?->duration) }}" placeholder="e.g. 8 weeks">
            <div class="field-error" data-error="duration"></div>
        </div>
        <div>
            <label class="field-label" for="c_price">Course Price (GHS)</label>
            <input type="number" id="c_price" name="price" min="0" step="0.01" value="{{ old('price', $model?->price) }}" placeholder="e.g. 500">
            <div class="field-error" data-error="price"></div>
        </div>
        <div>
            <label class="field-label" for="c_form_price">Form Fee (GHS)</label>
            <input type="number" id="c_form_price" name="form_price" min="0" step="0.01" value="{{ old('form_price', $model?->form_price) }}" placeholder="Default: 50">
            <div class="field-error" data-error="form_price"></div>
        </div>
        <div>
            <label class="field-label" for="c_image">Image</label>
            <div class="file-field">
                <span class="file-preview" data-preview-for="c_image">
                    @if ($isEdit && $model->image_url)
                        <img src="{{ $model->image_url }}" alt="">
                    @else
                        <i class="fa-regular fa-image"></i>
                    @endif
                </span>
                <label class="file-btn" for="c_image"><i class="fa-solid fa-upload"></i> Choose image</label>
                <input type="file" id="c_image" name="image" accept="image/jpeg,image/png,image/webp" data-preview="c_image" data-max-kb="2048" hidden>
                <span class="file-name" data-filename-for="c_image"></span>
            </div>
            @if ($isEdit)<div class="upload-hint">Leave empty to keep the current image.</div>@endif
            <div class="field-error" data-error="image"></div>
        </div>
        <div class="span-2">
            <label class="field-label" for="c_description">Description</label>
            <textarea id="c_description" name="description" rows="3" data-rich style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; font-size:1rem; font-family:inherit;">{{ old('description', $model?->description) }}</textarea>
            <div class="field-error" data-error="description"></div>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Add Course' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
