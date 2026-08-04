<form method="POST" action="{{ route('dashboard.permissions.store') }}" data-modal-form autocomplete="off" style="display:grid; gap:16px;">
    @csrf
    <div>
        <label class="field-label" for="p_name">Permission Name <span class="req">*</span></label>
        <input type="text" id="p_name" name="name" placeholder="e.g. manage reports" required>
        <div class="field-error" data-error="name"></div>
    </div>
    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">Add Permission</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
