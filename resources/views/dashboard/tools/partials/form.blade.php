@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.tools.update', $model) : route('dashboard.tools.store') }}"
      enctype="multipart/form-data" data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div>
            <label class="field-label" for="t_name">Name <span class="req">*</span></label>
            <input type="text" id="t_name" name="name" value="{{ old('name', $model?->name) }}" placeholder="e.g. Figma" required>
            <div class="field-error" data-error="name">@error('name'){{ $message }}@enderror</div>
        </div>
        <div>
            <label class="field-label" for="t_category">Category <span class="req">*</span></label>
            <select id="t_category" name="category" required>
                @foreach (array_keys(config('bootcamp.category_short_names')) as $category)
                    <option value="{{ $category }}" @selected(old('category', $model?->category) === $category)>{{ $category }}</option>
                @endforeach
            </select>
            <div class="field-error" data-error="category"></div>
        </div>
        <div class="span-2">
            <label class="field-label" for="t_blurb">Blurb</label>
            <textarea id="t_blurb" name="blurb" rows="3" placeholder="One short line about the tool" style="width:100%; font-family:inherit;">{{ old('blurb', $model?->blurb) }}</textarea>
            <div class="field-error" data-error="blurb"></div>
        </div>
        <div>
            <label class="field-label" for="t_image">Image</label>
            <div class="file-field">
                <span class="file-preview" data-preview-for="t_image">
                    @if ($isEdit && $model->image_url)
                        <img src="{{ $model->image_url }}" alt="">
                    @else
                        <i class="fa-regular fa-image"></i>
                    @endif
                </span>
                <label class="file-btn" for="t_image"><i class="fa-solid fa-upload"></i> Choose image</label>
                <input type="file" id="t_image" name="image" accept="image/jpeg,image/png,image/webp" data-preview="t_image" data-max-kb="2048" hidden>
                <span class="file-name" data-filename-for="t_image"></span>
            </div>
            @if ($isEdit)<div class="upload-hint">Leave empty to keep the current image{{ $model->image ? '' : ' (category default)' }}.</div>@endif
            <div class="field-error" data-error="image"></div>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Add Tool' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
