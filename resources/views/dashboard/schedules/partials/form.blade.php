@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.schedules.update', $model) : route('dashboard.schedules.store') }}"
      data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div class="span-2">
            <label class="field-label" for="s_week_label">Week Label <span class="req">*</span></label>
            <input type="text" id="s_week_label" name="week_label" value="{{ old('week_label', $model?->week_label) }}" placeholder="e.g. Weeks 1–2" required>
            <div class="field-error" data-error="week_label">@error('week_label'){{ $message }}@enderror</div>
        </div>
        <div class="span-2">
            <label class="field-label" for="s_focus">Focus <span class="req">*</span></label>
            <input type="text" id="s_focus" name="focus" value="{{ old('focus', $model?->focus) }}" placeholder="e.g. Task & Project Management — Trello, Basecamp, Notion" required>
            <div class="field-error" data-error="focus"></div>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Add Entry' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
