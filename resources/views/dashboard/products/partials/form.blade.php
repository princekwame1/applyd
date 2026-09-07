@php($isEdit = isset($model) && $model)
@php($delivery = old('delivery', $isEdit && $model->deliversLink() ? 'link' : 'file'))
<form method="POST"
      action="{{ $isEdit ? route('dashboard.products.update', $model) : route('dashboard.products.store') }}"
      enctype="multipart/form-data" data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div class="span-2">
            <label class="field-label" for="pr_title">Title <span class="req">*</span></label>
            <input type="text" id="pr_title" name="title" value="{{ old('title', $model?->title) }}" placeholder="e.g. Social Media Content Calendar" required>
            <div class="field-error" data-error="title">@error('title'){{ $message }}@enderror</div>
        </div>
        <div class="span-2">
            <label class="field-label" for="pr_tagline">Tagline</label>
            <input type="text" id="pr_tagline" name="tagline" value="{{ old('tagline', $model?->tagline) }}" placeholder="One line that says what it is">
            <div class="field-error" data-error="tagline"></div>
        </div>
        <div>
            <label class="field-label" for="pr_price">Price ({{ config('services.paystack.currency', 'GHS') }}) <span class="req">*</span></label>
            <input type="number" id="pr_price" name="price" step="0.01" min="0" value="{{ old('price', $isEdit ? number_format((float) $model->price, 2, '.', '') : '') }}" placeholder="0.00" required>
            <div class="upload-hint">Leave it at 0 to give it away — a free download skips payment entirely.</div>
            <div class="field-error" data-error="price">@error('price'){{ $message }}@enderror</div>
        </div>
        <div>
            <label class="field-label" for="pr_category">Category</label>
            <input type="text" id="pr_category" name="category" value="{{ old('category', $model?->category) }}" placeholder="e.g. Templates">
            <div class="field-error" data-error="category"></div>
        </div>
        <div>
            <label class="field-label" for="pr_format">Format</label>
            <input type="text" id="pr_format" name="format" value="{{ old('format', $model?->format) }}" placeholder="e.g. PDF · 24 pages">
            <div class="upload-hint">Shown to buyers so they know what they are getting.</div>
            <div class="field-error" data-error="format"></div>
        </div>
        <div>
            <label class="field-label" for="pr_cover">Cover Image</label>
            <div class="file-field">
                <span class="file-preview" data-preview-for="pr_cover">
                    @if ($isEdit && $model->cover_url)
                        <img src="{{ $model->cover_url }}" alt="">
                    @else
                        <i class="fa-regular fa-image"></i>
                    @endif
                </span>
                <label class="file-btn" for="pr_cover"><i class="fa-solid fa-upload"></i> Choose image</label>
                <input type="file" id="pr_cover" name="cover" accept="image/jpeg,image/png,image/webp" data-preview="pr_cover" data-max-kb="2048" hidden>
                <span class="file-name" data-filename-for="pr_cover"></span>
            </div>
            <div class="upload-hint">JPG, PNG or WebP · max 2&nbsp;MB{{ $isEdit ? ' · leave empty to keep current' : '' }}</div>
            <div class="field-error" data-error="cover"></div>
        </div>
        <div class="span-2">
            <label class="field-label" for="pr_description">Description</label>
            <textarea id="pr_description" name="description" rows="5" data-rich placeholder="What's inside, who it's for…">{{ old('description', $model?->description) }}</textarea>
            <div class="field-error" data-error="description"></div>
        </div>

        {{-- Delivery: a file we hold, or a link we send. Never both — picking
             one clears the other, so the buyer's page always has one answer. --}}
        <div class="span-2">
            <label class="field-label">How buyers get it <span class="req">*</span></label>
            <div class="qform-choices" style="margin-bottom:12px;">
                <label class="qform-choice">
                    <input type="radio" name="delivery" value="file" @checked($delivery === 'file')>
                    <span>Upload a file</span>
                </label>
                <label class="qform-choice">
                    <input type="radio" name="delivery" value="link" @checked($delivery === 'link')>
                    <span>Send them a link</span>
                </label>
            </div>

            <div data-delivery-panel="file" @if($delivery !== 'file') hidden @endif>
                <div class="file-field">
                    <span class="file-preview" data-preview-for="pr_file"><i class="fa-regular fa-file-lines"></i></span>
                    <label class="file-btn" for="pr_file"><i class="fa-solid fa-upload"></i> Choose file</label>
                    <input type="file" id="pr_file" name="file" data-preview="pr_file" data-max-kb="{{ \App\Support\ProductFiles::MAX_KB }}" hidden>
                    <span class="file-name" data-filename-for="pr_file">{{ $isEdit ? $model->file_name : '' }}</span>
                </div>
                <div class="upload-hint">
                    Stored privately — only somebody who has paid ever reaches it. Max {{ (int) (\App\Support\ProductFiles::MAX_KB / 1024) }}&nbsp;MB.
                    @if ($isEdit && $model->deliversFile())
                        <br>Currently: <strong>{{ $model->file_name }}</strong>{{ $model->file_size_label ? ' · '.$model->file_size_label : '' }} — leave empty to keep it.
                    @endif
                </div>
                <div class="field-error" data-error="file">@error('file'){{ $message }}@enderror</div>
            </div>

            <div data-delivery-panel="link" @if($delivery !== 'link') hidden @endif>
                <input type="url" id="pr_link" name="external_url" value="{{ old('external_url', $model?->external_url) }}" placeholder="https://drive.google.com/…">
                <div class="upload-hint">For anything too big to host here, or that lives somewhere else already.</div>
                <div class="field-error" data-error="external_url">@error('external_url'){{ $message }}@enderror</div>
            </div>
        </div>

        <div class="span-2">
            <label class="switch-row">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $isEdit ? $model->is_published : true))>
                <span>On sale <small>(uncheck to take it off the shop without deleting it)</small></span>
            </label>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Add Product' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
