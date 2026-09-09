@extends('layouts.company')

@section('title', 'Applications — '.$opening->title)

@section('content')
<a href="{{ route('company.home') }}" class="tool-link">← My jobs</a>
<div class="page-head" style="margin-top: 8px;">
    <h1 class="section-title">Applications: {{ $opening->title }}</h1>
    <a class="btn btn-outline btn-sm" href="{{ route('company.screening', $opening) }}">
        {{ $criteria->isEmpty() ? 'Set screening criteria' : 'Edit screening criteria' }}
    </a>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

@if ($criteria->isEmpty() && $total > 0)
    {{-- The pitch, shown where the work actually is. --}}
    <div class="card screen-prompt">
        <h3>Score these applicants automatically</h3>
        <p>
            Tell us what the role needs — the skills to look for in a CV, the questions worth asking up front —
            and every application is scored against it, best fit first. Everyone who has already applied is
            re-scored too.
        </p>
        <a class="btn btn-brand btn-sm" href="{{ route('company.screening', $opening) }}">Set screening criteria</a>
    </div>
@endif

@if ($total > 0)
    <div class="fit-filters">
        @php
            $tabs = [
                '' => $total.' total',
                'top' => $topCount.' top matches',
                'missing' => $missingCount.' missing a must-have',
            ];
        @endphp
        @foreach ($tabs as $value => $label)
            @continue($value !== '' && $criteria->isEmpty())
            <a class="fit-tab {{ (string) $fit === (string) $value ? 'is-active' : '' }}"
               href="{{ route('company.applications', ['opening' => $opening, 'fit' => $value ?: null, 'status' => $status]) }}">{{ $label }}</a>
        @endforeach

        <form method="GET" action="{{ route('company.applications', $opening) }}" class="fit-status">
            <input type="hidden" name="fit" value="{{ $fit }}">
            <select name="status" data-no-select2 onchange="this.form.submit()">
                <option value="">Any status</option>
                @foreach (\App\Models\JobApplication::STATUSES as $option)
                    <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="btn btn-sm btn-outline">Filter</button></noscript>
        </form>
    </div>
@endif

@forelse ($applications as $application)
    <div class="card applicant-card">
        <div class="applicant-head">
            <div>
                <h3>
                    {{ $application->full_name }}
                    @if ($application->missesRequirement())
                        <span class="fit-flag" title="{{ implode(', ', $application->missedLabels()) }}">Missing a must-have</span>
                    @endif
                </h3>
                <p class="applicant-meta">
                    <a href="mailto:{{ $application->email }}">{{ $application->email }}</a>
                    {{ $application->phone ? ' · '.$application->phone : '' }}
                    · Applied {{ $application->created_at->format('M j, Y g:ia') }}
                </p>
            </div>
            <div class="applicant-head-right">
                @if ($application->isScored())
                    <span class="fit-score {{ $application->isTopMatch() ? 'is-top' : '' }} {{ $application->missesRequirement() ? 'is-flagged' : '' }}">
                        <strong>{{ $application->fit_score }}%</strong>
                        <small>fit</small>
                    </span>
                @endif
                <span class="status-chip status-{{ $application->status }}">{{ ucfirst($application->status) }}</span>
            </div>
        </div>

        @if ($application->fit_breakdown)
            {{-- Closed by default and native, so the reasoning is one click
                 away without JS and the list stays scannable. --}}
            <details class="fit-detail">
                <summary>
                    How this was scored
                    <span>
                        {{ collect($application->fit_breakdown)->where('result', 'met')->count() }}
                        of {{ count($application->fit_breakdown) }} met
                    </span>
                </summary>
                <ul class="fit-lines">
                    @foreach ($application->fit_breakdown as $line)
                        <li class="fit-line is-{{ $line['result'] }}">
                            <span class="fit-mark" aria-hidden="true">
                                {{ $line['result'] === 'met' ? '✓' : ($line['result'] === 'missed' ? '✕' : '?') }}
                            </span>
                            <span>
                                <strong>{{ $line['label'] }}</strong>
                                @if (! empty($line['knockout']))<span class="fit-must">must-have</span>@endif
                                @if (($line['weight'] ?? 1) > 1)<span class="fit-weight">×{{ $line['weight'] }}</span>@endif
                                <small>{{ $line['detail'] }}</small>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif

        @if ($application->cover_letter)
            <p class="applicant-cover">{{ $application->cover_letter }}</p>
        @endif

        <div class="applicant-files">
            {{-- The href is the download, so this still works with no JS at
                 all; script turns the same click into the viewer instead. --}}
            <a class="file-pill" href="{{ route('company.applications.cv', $application) }}"
               data-view="{{ route('company.applications.cv.view', $application) }}"
               data-name="CV — {{ $application->cv_name }}">
                <i class="fa-regular fa-file-lines" aria-hidden="true"></i> CV — {{ $application->cv_name }}
            </a>
            @foreach ($application->documents as $document)
                <a class="file-pill" href="{{ route('company.applications.document', $document) }}"
                   data-view="{{ route('company.applications.document.view', $document) }}"
                   data-name="{{ $document->original_name }}">
                    <i class="fa-solid fa-paperclip" aria-hidden="true"></i> {{ $document->original_name }}
                </a>
            @endforeach
        </div>

        <form method="POST" action="{{ route('company.applications.status', $application) }}" class="status-form">
            @csrf
            @method('PATCH')
            <label class="field-label" style="margin: 0;">Status:</label>
            <select name="status" data-no-select2 style="width:auto; padding: 6px 10px;">
                @foreach (\App\Models\JobApplication::STATUSES as $option)
                    <option value="{{ $option }}" @selected($application->status === $option)>{{ ucfirst($option) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-outline">Update</button>
        </form>
    </div>
@empty
    <div class="card center" style="padding: 48px;">
        @if ($total > 0)
            <h3 style="margin-bottom: 8px;">Nobody matches this filter</h3>
            <p style="color: var(--ink-soft);">
                {{ $total }} {{ Str::plural('application', $total) }} in total —
                <a class="tool-link" href="{{ route('company.applications', $opening) }}">show all of them</a>.
            </p>
        @else
            <h3 style="margin-bottom: 8px;">No applications yet</h3>
            <p style="color: var(--ink-soft);">Share your job link to reach more candidates:</p>
            <p style="margin-top: 8px;"><a class="tool-link" href="{{ route('jobs.show', $opening) }}">{{ route('jobs.show', $opening) }}</a></p>
        @endif
    </div>
@endforelse

{{-- The viewer. One frame, reused by every file on the page — the documents
     are private, so it points at the same owner-checked routes the download
     buttons use rather than at anything public. --}}
<div class="modal-overlay doc-viewer" id="docViewer" hidden>
    <div class="modal-panel doc-panel" role="dialog" aria-modal="true" aria-labelledby="docViewerTitle">
        <div class="modal-header">
            <h3 class="modal-title" id="docViewerTitle">Document</h3>
            <div class="doc-actions">
                <a class="btn btn-sm btn-outline" id="docViewerDownload" href="#">Download</a>
                <button type="button" class="modal-x" data-close-viewer aria-label="Close">&times;</button>
            </div>
        </div>
        <div class="modal-body doc-body">
            <iframe id="docViewerFrame" title="Document preview" src="about:blank"></iframe>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var overlay = document.getElementById('docViewer');
        var frame = document.getElementById('docViewerFrame');
        var title = document.getElementById('docViewerTitle');
        var download = document.getElementById('docViewerDownload');

        function close() {
            overlay.hidden = true;
            // Emptied on close: a PDF left in a hidden frame keeps its plugin
            // and its memory, and there may be a dozen of these on a page.
            frame.src = 'about:blank';
            document.body.style.overflow = '';
        }

        document.addEventListener('click', function (e) {
            var pill = e.target.closest('[data-view]');

            if (pill) {
                e.preventDefault();
                title.textContent = pill.dataset.name || 'Document';
                download.href = pill.getAttribute('href');
                frame.src = pill.dataset.view;
                overlay.hidden = false;
                document.body.style.overflow = 'hidden';
                return;
            }

            if (e.target.closest('[data-close-viewer]') || e.target === overlay) close();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !overlay.hidden) close();
        });
    })();
</script>
@endpush
