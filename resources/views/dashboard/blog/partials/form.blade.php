@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.blog.update', $model) : route('dashboard.blog.store') }}"
      enctype="multipart/form-data" data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div class="span-2">
            <label class="field-label" for="p_title">Title <span class="req">*</span></label>
            <input type="text" id="p_title" name="title" value="{{ old('title', $model?->title) }}" placeholder="e.g. 5 Marketing Tools Every Beginner Should Know" required>
            <div class="field-error" data-error="title">@error('title'){{ $message }}@enderror</div>
        </div>
        <div>
            <label class="field-label" for="p_category">Category</label>
            <select id="p_category" name="blog_category_id">
                <option value="">Uncategorized</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('blog_category_id', $model?->blog_category_id) === $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <div class="field-error" data-error="blog_category_id"></div>
        </div>
        <div>
            <label class="field-label" for="p_author">Author</label>
            <input type="text" id="p_author" name="author" value="{{ old('author', $model?->author) }}" placeholder="Defaults to you">
            <div class="field-error" data-error="author"></div>
        </div>
        <div class="span-2">
            <label class="field-label" for="p_cover">Cover Image</label>
            <div class="file-field">
                <span class="file-preview" data-preview-for="p_cover">
                    @if ($isEdit && $model->cover_image_url)
                        <img src="{{ $model->cover_image_url }}" alt="">
                    @else
                        <i class="fa-regular fa-image"></i>
                    @endif
                </span>
                <label class="file-btn" for="p_cover"><i class="fa-solid fa-upload"></i> Choose image</label>
                <input type="file" id="p_cover" name="cover_image" accept="image/jpeg,image/png,image/webp" data-preview="p_cover" data-max-kb="2048" hidden>
                <span class="file-name" data-filename-for="p_cover"></span>
            </div>
            <div class="upload-hint">JPG, PNG or WebP · max 2&nbsp;MB{{ $isEdit ? ' · leave empty to keep current' : '' }}</div>
            <div class="field-error" data-error="cover_image"></div>
        </div>
        <div class="span-2">
            <label class="field-label" for="p_excerpt">Excerpt</label>
            <textarea id="p_excerpt" name="excerpt" rows="2" placeholder="Short summary shown on the blog grid (optional)" style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:8px; font-size:1rem; font-family:inherit;">{{ old('excerpt', $model?->excerpt) }}</textarea>
            <div class="field-error" data-error="excerpt"></div>
        </div>
        <div class="span-2">
            <label class="field-label" for="p_body">Body <span class="req">*</span></label>
            <textarea id="p_body" name="body" rows="6" data-rich placeholder="Write your post…">{{ old('body', $model?->body) }}</textarea>
            <div class="field-error" data-error="body">@error('body'){{ $message }}@enderror</div>
        </div>
        <div class="span-2">
            <label class="switch-row">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $isEdit ? $model->is_published : true))>
                <span>Published <small>(uncheck to save as draft)</small></span>
            </label>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Publish Post' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
