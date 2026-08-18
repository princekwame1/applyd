<div style="display:flex; gap:8px; align-items:center; white-space:nowrap;">
    <a class="btn btn-sm btn-outline" style="padding:6px 12px; font-size:.85rem;"
       href="{{ route('dashboard.email-logs.show', $id) }}" target="_blank" rel="noopener">View</a>

    <button type="button" class="btn btn-brand btn-sm" style="padding:6px 12px; font-size:.85rem;"
            wire:click="resend({{ $id }})" wire:loading.attr="disabled" wire:target="resend({{ $id }})">
        <span wire:loading.remove wire:target="resend({{ $id }})">Resend</span>
        <span wire:loading wire:target="resend({{ $id }})">Sending…</span>
    </button>
    @include('dashboard.partials.row-delete', ['id' => $id])
</div>
