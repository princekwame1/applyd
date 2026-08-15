@use('App\Models\FinanceTransaction')
@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.finance.categories.update', $model) : route('dashboard.finance.categories.store') }}"
      data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div>
            <label class="field-label" for="c_name">What do you call it? <span class="req">*</span></label>
            <input type="text" id="c_name" name="name" value="{{ old('name', $model?->name) }}"
                   placeholder="e.g. Venue &amp; logistics" required>
            <div class="field-error" data-error="name">@error('name'){{ $message }}@enderror</div>
        </div>

        <div>
            <label class="field-label" for="c_type">Which side of the books? <span class="req">*</span></label>
            <select id="c_type" name="type" required>
                @foreach (FinanceTransaction::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $model?->type ?? FinanceTransaction::EXPENSE) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="upload-hint">
                @if ($isEdit && $model->transactions()->exists())
                    <strong>Fixed</strong> — entries are already filed under this heading.
                @else
                    The same name can exist on both sides.
                @endif
            </div>
            <div class="field-error" data-error="type"></div>
        </div>

        <div class="span-2">
            <label class="field-label" for="c_note">A note for whoever's keying entries</label>
            <input type="text" id="c_note" name="note" value="{{ old('note', $model?->note) }}"
                   placeholder="e.g. Hall hire, chairs, generator — not transport.">
            <div class="upload-hint">Optional. Useful when two categories could plausibly fit.</div>
            <div class="field-error" data-error="note"></div>
        </div>

        <div class="span-2">
            <label class="field-label">Still in use?</label>
            <label class="switch-row">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $model?->is_active ?? true))>
                <span>Offer it when recording entries <small>(off = retired, old entries keep it)</small></span>
            </label>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save changes' : 'Add category' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
