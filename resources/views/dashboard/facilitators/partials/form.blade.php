@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.facilitators.update', $model) : route('dashboard.facilitators.store') }}"
      data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div class="span-2">
            <label class="field-label" for="f_name">Full Name <span class="req">*</span></label>
            <input type="text" id="f_name" name="name" value="{{ old('name', $model?->name) }}" placeholder="e.g. Kwame Boateng" required>
            <div class="field-error" data-error="name">@error('name'){{ $message }}@enderror</div>
        </div>
        <div>
            <label class="field-label" for="f_email">Email <span class="req">*</span></label>
            <input type="email" id="f_email" name="email" value="{{ old('email', $model?->email) }}" required>
            <div class="upload-hint">This is their username for the portal.</div>
            <div class="field-error" data-error="email">@error('email'){{ $message }}@enderror</div>
        </div>
        <div>
            <label class="field-label" for="f_phone">Phone</label>
            <input type="tel" id="f_phone" name="phone" value="{{ old('phone', $model?->phone) }}" placeholder="e.g. 0240835458">
            <div class="upload-hint">Optional. With a number, the login goes by SMS as well as email.</div>
            <div class="field-error" data-error="phone">@error('phone'){{ $message }}@enderror</div>
        </div>

        @unless ($isEdit)
            <div class="span-2">
                <label class="switch-row">
                    <input type="hidden" name="send_credentials" value="0">
                    <input type="checkbox" name="send_credentials" value="1" @checked(old('send_credentials', true))>
                    <span>Send their login now <small>(uncheck to add them quietly and send it later)</small></span>
                </label>
                <div class="upload-hint" style="margin-top:8px;">
                    We generate the password. If this email already has an account, they keep the password
                    they have and simply gain facilitator access.
                </div>
            </div>
        @else
            <div class="span-2">
                <div class="upload-hint">
                    Passwords aren't set from here. Use <strong>Resend login details</strong> on their row to
                    issue a new temporary one.
                </div>
            </div>
        @endunless
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Add Facilitator' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
