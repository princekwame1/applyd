@php($isEdit = isset($model) && $model)
@php($isSuper = $isEdit && $model->name === 'super')
<form method="POST"
      action="{{ $isEdit ? route('dashboard.roles.update', $model) : route('dashboard.roles.store') }}"
      data-modal-form autocomplete="off" style="display:grid; gap:16px;">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div>
        <label class="field-label" for="r_name">Role Name <span class="req">*</span></label>
        <input type="text" id="r_name" name="name" value="{{ old('name', $model?->name) }}" placeholder="e.g. moderator" {{ $isSuper ? 'readonly' : '' }} required>
        <div class="field-error" data-error="name">@error('name'){{ $message }}@enderror</div>
    </div>

    <div>
        <label class="field-label">Permissions</label>
        @if ($isSuper)
            <p style="color:var(--ink-soft); font-size:.9rem;">The super role always has every permission.</p>
        @endif
        <div class="perm-options">
            @foreach ($permissions as $permission)
                <label class="chk">
                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                        @checked(in_array($permission->name, old('permissions', $isEdit ? $model->permissions->pluck('name')->all() : [])))
                        @disabled($isSuper)>
                    {{ $permission->name }}
                </label>
            @endforeach
        </div>
        <div class="field-error" data-error="permissions"></div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Create Role' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
