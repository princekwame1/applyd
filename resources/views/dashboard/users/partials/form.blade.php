@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.users.update', $model) : route('dashboard.users.store') }}"
      data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div>
            <label class="field-label" for="u_name">Name <span class="req">*</span></label>
            <input type="text" id="u_name" name="name" value="{{ old('name', $model?->name) }}" required>
            <div class="field-error" data-error="name">@error('name'){{ $message }}@enderror</div>
        </div>
        <div>
            <label class="field-label" for="u_email">Email <span class="req">*</span></label>
            <input type="email" id="u_email" name="email" value="{{ old('email', $model?->email) }}" required>
            <div class="field-error" data-error="email"></div>
        </div>
        <div class="span-2">
            <label class="field-label" for="u_role">Role <span class="req">*</span></label>
            <select id="u_role" name="role" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(old('role', $isEdit ? $model->getRoleNames()->first() : 'student') === $role->name)>{{ ucfirst($role->name) }}</option>
                @endforeach
            </select>
            <div class="field-error" data-error="role"></div>
        </div>
        <div>
            <label class="field-label" for="u_password">
                {{ $isEdit ? 'New Password' : 'Password' }}
                @if ($isEdit) <small>(leave empty to keep)</small> @else <span class="req">*</span> @endif
            </label>
            <input type="password" id="u_password" name="password" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
            <div class="field-error" data-error="password"></div>
        </div>
        <div>
            <label class="field-label" for="u_password_confirmation">Confirm Password @unless ($isEdit) <span class="req">*</span> @endunless</label>
            <input type="password" id="u_password_confirmation" name="password_confirmation" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Create User' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
