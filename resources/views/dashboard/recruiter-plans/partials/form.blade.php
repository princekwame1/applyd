@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.recruiter-plans.update', $model) : route('dashboard.recruiter-plans.store') }}"
      data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div>
            <label class="field-label" for="p_name">Plan name <span class="req">*</span></label>
            <input type="text" id="p_name" name="name" value="{{ old('name', $model?->name) }}" placeholder="e.g. Growth" required>
            <div class="field-error" data-error="name">@error('name'){{ $message }}@enderror</div>
        </div>
        <div>
            <label class="field-label" for="p_slug">Slug</label>
            <input type="text" id="p_slug" name="slug" value="{{ old('slug', $model?->slug) }}" placeholder="Built from the name if left empty">
            <div class="field-error" data-error="slug">@error('slug'){{ $message }}@enderror</div>
        </div>
        <div>
            <label class="field-label" for="p_price">Price ({{ config('services.paystack.currency', 'GHS') }}) <span class="req">*</span></label>
            <input type="number" id="p_price" name="price" step="0.01" min="0" value="{{ old('price', $model?->price) }}" required>
            <div class="field-error" data-error="price"></div>
        </div>
        <div>
            <label class="field-label" for="p_credits">CV unlocks <span class="req">*</span></label>
            <input type="number" id="p_credits" name="cv_credits" min="1" value="{{ old('cv_credits', $model?->cv_credits) }}" required>
            <div class="upload-hint">How many candidate CVs this plan opens.</div>
            <div class="field-error" data-error="cv_credits"></div>
        </div>
        <div class="span-2">
            <label class="field-label" for="p_blurb">Blurb</label>
            <input type="text" id="p_blurb" name="blurb" value="{{ old('blurb', $model?->blurb) }}" placeholder="One line under the price">
            <div class="field-error" data-error="blurb"></div>
        </div>
        <div class="span-2">
            <label class="field-label" for="p_features">Features</label>
            <textarea id="p_features" name="features" rows="4" style="width:100%; font-family:inherit;"
                      placeholder="One per line.&#10;25 CV unlocks&#10;Unlimited job posts">{{ old('features', $model ? implode("\n", $model->featureList()) : '') }}</textarea>
            <div class="upload-hint">One per line — shown as ticks on the plan card.</div>
            <div class="field-error" data-error="features"></div>
        </div>
        <div>
            <label class="field-label">On sale</label>
            <label class="switch-row">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isEdit ? $model->is_active : true))>
                <span>Show it to recruiters</span>
            </label>
        </div>
        <div>
            <label class="field-label">Featured</label>
            <label class="switch-row">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $model?->is_featured ?? false))>
                <span>Flag it “Most popular”</span>
            </label>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Add Plan' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
