<div class="detail-grid" style="padding: 4px 0 8px;">
    <div class="detail-item"><div class="lbl">Form</div><div class="val">{{ $response->questionnaire?->title ?? '—' }}</div></div>
    <div class="detail-item"><div class="lbl">Reference</div><div class="val"><code>{{ $response->reference }}</code></div></div>
    <div class="detail-item"><div class="lbl">Submitted</div><div class="val">{{ $response->created_at->format('M j, Y g:ia') }}</div></div>

    @foreach ($questions as $question)
        @php($value = $response->answer($question->key))
        @php($file = $question->isFile() ? $response->fileFor($question->key) : null)
        <div class="detail-item" style="grid-column: 1 / -1;">
            <div class="lbl">{{ $question->label }}@unless ($question->is_active) <small style="color:var(--ink-soft);">(hidden question)</small>@endunless</div>
            <div class="val">
                @if ($file)
                    <a href="{{ route('dashboard.questionnaires.file', $file) }}">{{ $file->original_name }}</a>
                    <small style="color:var(--ink-soft);">({{ $file->humanSize() }})</small>
                @elseif ($question->formatAnswer($value) === '')
                    <em style="color:var(--ink-soft); font-weight:400;">Skipped</em>
                @elseif (is_array($value))
                    <ul style="margin:0; padding-left:18px;">
                        @foreach ($value as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @else
                    {{ $value }}
                @endif
            </div>
        </div>
    @endforeach
</div>

<div class="modal-actions">
    <button type="button" class="btn btn-sm btn-outline" data-modal-close>Close</button>
</div>
