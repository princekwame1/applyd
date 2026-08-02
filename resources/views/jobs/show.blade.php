@extends('layouts.app')

@section('title', $opening->title.' at '.$opening->company->name.' — Applyd Academy')

@section('content')
<section class="alt contact-hero">
    <div class="container">
        <a href="{{ route('jobs') }}" class="tool-link">← All jobs</a>
        <h1 class="section-title" style="margin-top: 10px;">{{ $opening->title }}</h1>
        <p class="section-lead" style="margin-bottom: 14px;">{{ $opening->company->name }}{{ $opening->location ? ' · '.$opening->location : '' }}</p>
        <div class="job-meta-row">
            <span class="tag">{{ $opening->type }}</span>
            @if ($opening->salary_range)<span class="tag">{{ $opening->salary_range }}</span>@endif
            @if ($opening->deadline)<span class="tag">Apply by {{ $opening->deadline->format('M j, Y') }}</span>@endif
            @unless ($opening->is_accepting)<span class="tag" style="background:#fef2f2;color:var(--danger);">Closed</span>@endunless
        </div>
    </div>
</section>

<section>
    <div class="container">
        <div class="split job-split">
            <div>
                <h2 class="section-title" style="font-size:1.3rem;">About the role</h2>
                <div class="job-description">{!! nl2br(e($opening->description)) !!}</div>

                @if ($opening->company->description)
                    <h2 class="section-title" style="font-size:1.3rem; margin-top: 32px;">About {{ $opening->company->name }}</h2>
                    <p style="color:var(--ink-soft);">{{ $opening->company->description }}</p>
                @endif
                @if ($opening->company->website)
                    <p style="margin-top:10px;"><a class="tool-link" href="{{ $opening->company->website }}" target="_blank" rel="noopener">Visit website →</a></p>
                @endif
            </div>

            <div class="split-media" style="padding: 0;">
                <div class="form-card" style="max-width:none; padding: 26px;">
                    <h2 class="form-title" style="font-size:1.25rem; margin-bottom: 2px;">Apply for this job</h2>
                    <p class="form-sub" style="margin-bottom: 16px;">Upload your CV and any supporting documents.</p>

                    @if (session('applied'))
                        <div class="success-box">{{ session('applied') }}</div>
                    @elseif (! $opening->is_accepting)
                        <div class="error-box">This position is no longer accepting applications.</div>
                    @else
                        @if ($errors->any())
                            <div class="error-box">Please fix the highlighted fields below and try again.</div>
                        @endif

                        <form method="POST" action="{{ route('jobs.apply', $opening) }}" enctype="multipart/form-data" style="display:grid; gap:14px;">
                            @csrf
                            <div>
                                <label class="field-label" for="full_name">Full Name <span class="req">*</span></label>
                                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required>
                                @error('full_name') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="email">Email <span class="req">*</span></label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                                @error('email') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="cover_letter">Cover Letter</label>
                                <textarea id="cover_letter" name="cover_letter" rows="4" placeholder="Tell {{ $opening->company->name }} why you're a great fit…" style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:1rem; font-family:inherit;">{{ old('cover_letter') }}</textarea>
                                @error('cover_letter') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="cv">CV / Résumé <span class="req">*</span> <small>(PDF or Word, max 4 MB)</small></label>
                                <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" required>
                                @error('cv') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="documents">Supporting Documents <small>(optional, up to 5 — certificates, portfolio, etc.)</small></label>
                                <input type="file" id="documents" name="documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple>
                                @error('documents') <div class="field-error">{{ $message }}</div> @enderror
                                @error('documents.*') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-brand" style="width:100%;">Submit Application</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
