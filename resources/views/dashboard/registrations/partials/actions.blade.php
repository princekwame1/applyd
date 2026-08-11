<div style="display:flex; gap:8px; align-items:center; white-space:nowrap;">
    <a class="btn btn-sm btn-outline" style="padding:6px 12px; font-size:.85rem;" href="{{ route('dashboard.show', $id) }}">View</a>

    <button type="button" class="btn btn-brand btn-sm" style="padding:6px 12px; font-size:.85rem;"
            wire:click="resendEmail({{ $id }})" wire:loading.attr="disabled" wire:target="resendEmail({{ $id }})">
        <span wire:loading.remove wire:target="resendEmail({{ $id }})">Resend email</span>
        <span wire:loading wire:target="resendEmail({{ $id }})">Sending…</span>
    </button>

    @can('manage registrations')
        <button type="button" class="btn btn-sm btn-danger" style="padding:6px 12px; font-size:.85rem;"
                wire:click="deleteRow({{ $id }})" wire:loading.attr="disabled" wire:target="performDelete({{ $id }})">
            <span wire:loading.remove wire:target="performDelete({{ $id }})">Delete</span>
            <span wire:loading wire:target="performDelete({{ $id }})">Deleting…</span>
        </button>
    @endcan
</div>
