@use('App\Models\FinanceCategory')
@use('App\Models\FinanceDocument')
@use('App\Models\FinanceTransaction')
@php($isEdit = isset($model) && $model)
@php($type = old('type', $model?->type ?? FinanceTransaction::EXPENSE))
<form method="POST"
      action="{{ $isEdit ? route('dashboard.finance.update', $model) : route('dashboard.finance.store') }}"
      data-modal-form autocomplete="off" enctype="multipart/form-data">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div>
            <label class="field-label" for="t_type">Is this money in or out? <span class="req">*</span></label>
            <select id="t_type" name="type" required data-entry-type data-no-select2>
                @foreach (FinanceTransaction::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="field-error" data-error="type"></div>
        </div>

        <div>
            <label class="field-label" for="t_amount">How much? <span class="req">*</span></label>
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="color:var(--ink-soft); font-size:.86rem;">{{ App\Support\Finance::currency() }}</span>
                <input type="number" id="t_amount" name="amount" step="0.01" min="0.01"
                       value="{{ old('amount', $model?->amount) }}" placeholder="0.00" required style="flex:1;">
            </div>
            <div class="field-error" data-error="amount">@error('amount'){{ $message }}@enderror</div>
        </div>

        <div>
            <label class="field-label" for="t_occurred_on">When did it happen? <span class="req">*</span></label>
            <input type="date" id="t_occurred_on" name="occurred_on" max="{{ now()->toDateString() }}"
                   value="{{ old('occurred_on', $model?->occurred_on?->toDateString() ?? now()->toDateString()) }}" required>
            <div class="upload-hint">The date the money actually moved, not today's date.</div>
            <div class="field-error" data-error="occurred_on">@error('occurred_on'){{ $message }}@enderror</div>
        </div>

        <div>
            <label class="field-label" for="t_category">What was it for?</label>
            <select id="t_category" name="finance_category_id" data-entry-category data-no-select2>
                <option value="">No category</option>
                @foreach (FinanceCategory::active()->ordered()->get() as $category)
                    <option value="{{ $category->id }}" data-type="{{ $category->type }}"
                            @selected((int) old('finance_category_id', $model?->finance_category_id) === $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <div class="upload-hint">Only headings from the matching side are offered.</div>
            <div class="field-error" data-error="finance_category_id">@error('finance_category_id'){{ $message }}@enderror</div>
        </div>

        <div>
            <label class="field-label" for="t_party"><span data-party-hint>Who did you pay?</span></label>
            <input type="text" id="t_party" name="party" value="{{ old('party', $model?->party) }}"
                   placeholder="e.g. Accra Conference Centre">
            <div class="field-error" data-error="party"></div>
        </div>

        <div>
            <label class="field-label" for="t_method">How was it paid?</label>
            <select id="t_method" name="method">
                <option value="">Not recorded</option>
                @foreach (FinanceTransaction::METHODS as $method)
                    <option value="{{ $method }}" @selected(old('method', $model?->method) === $method)>{{ $method }}</option>
                @endforeach
            </select>
            <div class="field-error" data-error="method"></div>
        </div>

        <div>
            <label class="field-label" for="t_document_no">Their invoice or receipt number</label>
            <input type="text" id="t_document_no" name="document_no" value="{{ old('document_no', $model?->document_no) }}"
                   placeholder="e.g. INV-2026-0184">
            <div class="upload-hint">Optional — handy when someone queries a payment later.</div>
            <div class="field-error" data-error="document_no"></div>
        </div>

        <div class="span-2">
            <label class="field-label" for="t_note">Anything worth remembering?</label>
            <textarea id="t_note" name="note" rows="2" style="width:100%; font-family:inherit;"
                      placeholder="e.g. Deposit for the September cohort venue — balance due before the 20th.">{{ old('note', $model?->note) }}</textarea>
            <div class="field-error" data-error="note"></div>
        </div>

        @php($maxMb = round(FinanceDocument::MAX_KB / 1024))
        @php($accept = collect(explode(',', FinanceDocument::MIMES))->map(fn ($e) => '.'.$e)->implode(','))

        <div>
            <label class="field-label" for="t_invoice">Invoice</label>
            <input type="file" id="t_invoice" name="invoice" accept="{{ $accept }}">
            <div class="upload-hint">
                @if ($isEdit && $model->invoice())
                    Replaces <strong>{{ $model->invoice()->original_name }}</strong>.
                @else
                    PDF, image or Office file, up to {{ $maxMb }} MB.
                @endif
            </div>
            <div class="field-error" data-error="invoice">@error('invoice'){{ $message }}@enderror</div>
        </div>

        <div>
            <label class="field-label" for="t_receipt">Receipt</label>
            <input type="file" id="t_receipt" name="receipt" accept="{{ $accept }}">
            <div class="upload-hint">
                @if ($isEdit && $model->receipt())
                    Replaces <strong>{{ $model->receipt()->original_name }}</strong>.
                @else
                    Proof the money moved. You can add more documents later.
                @endif
            </div>
            <div class="field-error" data-error="receipt">@error('receipt'){{ $message }}@enderror</div>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save changes' : 'Record it' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
