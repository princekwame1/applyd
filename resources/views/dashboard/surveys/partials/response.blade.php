<div class="detail-grid" style="padding: 4px 0 8px;">
    <div class="detail-item"><div class="lbl">Survey</div><div class="val">{{ $response->survey_label }}</div></div>
    <div class="detail-item"><div class="lbl">Submitted</div><div class="val">{{ $response->created_at->format('M j, Y g:ia') }}</div></div>

    @foreach ($questions as $question)
        @php($value = $response->answer($question->key))
        <div class="detail-item" style="grid-column: 1 / -1;">
            <div class="lbl">{{ $question->prompt }}</div>
            <div class="val">
                @if ($value === null)
                    <em style="color:var(--ink-soft); font-weight:400;">Skipped</em>
                @else
                    {{ $question->labelFor($value) }}
                @endif
            </div>
        </div>
    @endforeach
</div>

<div class="modal-actions">
    <button type="button" class="btn btn-sm btn-outline" data-modal-close>Close</button>
</div>
