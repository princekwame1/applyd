<div style="display:flex; gap:8px; align-items:center; white-space:nowrap;">
    @if ($status === 'failed')
        <button type="button" class="btn btn-brand btn-sm" style="padding:6px 12px; font-size:.85rem;"
                wire:click="retry({{ $id }})" wire:loading.attr="disabled" wire:target="retry({{ $id }})">
            <span wire:loading.remove wire:target="retry({{ $id }})">Retry</span>
            <span wire:loading wire:target="retry({{ $id }})">Sending…</span>
        </button>
    @endif

    @include('dashboard.partials.row-delete', ['id' => $id])
</div>
