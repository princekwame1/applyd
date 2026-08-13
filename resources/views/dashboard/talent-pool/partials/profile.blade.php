<div class="detail-grid" style="padding: 4px 0 8px;">
    <div class="detail-item"><div class="lbl">Name</div><div class="val">{{ $profile->full_name }}</div></div>
    <div class="detail-item"><div class="lbl">Email</div><div class="val">{{ $profile->email }}</div></div>
    <div class="detail-item"><div class="lbl">Phone</div><div class="val">{{ $profile->phone ?: '—' }}</div></div>
    <div class="detail-item"><div class="lbl">Based in</div><div class="val">{{ $profile->location ?: '—' }}</div></div>
    <div class="detail-item"><div class="lbl">Headline</div><div class="val">{{ $profile->headline ?: '—' }}</div></div>
    <div class="detail-item"><div class="lbl">Status</div><div class="val">{{ $profile->is_available ? 'Open to work' : 'Paused' }}</div></div>

    <div class="detail-item" style="grid-column: 1 / -1;">
        <div class="lbl">Sectors</div>
        <div class="val">{{ implode(', ', $profile->sectorList()) }}</div>
    </div>

    @if ($profile->summary)
        <div class="detail-item" style="grid-column: 1 / -1;">
            <div class="lbl">About</div>
            <div class="val">{{ $profile->summary }}</div>
        </div>
    @endif

    <div class="detail-item" style="grid-column: 1 / -1;">
        <div class="lbl">Unlocked by</div>
        <div class="val">
            @forelse ($profile->unlocks as $unlock)
                {{ $unlock->company?->name ?? 'Deleted company' }} <small style="color:var(--ink-soft);">({{ $unlock->created_at->format('M j, Y') }})</small>@if (! $loop->last), @endif
            @empty
                Nobody yet
            @endforelse
        </div>
    </div>
</div>

<div class="modal-actions">
    <a class="btn btn-sm btn-brand" href="{{ route('dashboard.talent-pool.cv', $profile) }}">Download CV</a>
    <button type="button" class="btn btn-sm btn-outline" data-modal-close>Close</button>
</div>
