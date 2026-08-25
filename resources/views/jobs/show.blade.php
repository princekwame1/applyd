@extends('layouts.app')

@section('title', $opening->title.' at '.$opening->company->name.' — Applyd Academy')
@section('og_title', $opening->title.' at '.$opening->company->name)
@section('og_description', \Illuminate\Support\Str::limit(trim(strip_tags((string) $opening->description)), 160) ?: '')
@section('og_image', $opening->company->logo_url ?? '')

@section('content')
<section class="alt job-detail-hero">
    <div class="container">
        <a href="{{ route('jobs') }}" class="job-back">← All jobs</a>
        <div class="job-hero-top">
            <div>
                <h1 class="job-detail-title">{{ $opening->title }}</h1>
                <p class="job-detail-company">{{ $opening->company->name }}@if ($opening->location) <span>· {{ $opening->location }}</span>@endif</p>
            </div>
        </div>
        <div class="job-meta-row">
            <span class="tag">{{ $opening->type }}</span>
            @if ($opening->sector)<span class="tag">{{ $opening->sector }}</span>@endif
            @if ($opening->salary_range)<span class="tag tag-salary">{{ $opening->salary_range }}</span>@endif
            @if ($opening->deadline)<span class="tag">Apply by {{ $opening->deadline->format('M j, Y') }}</span>@endif
            @unless ($opening->is_accepting)<span class="tag tag-closed">Closed</span>@endunless
        </div>
    </div>
</section>

<section>
    <div class="container">
        <div class="job-detail-grid">
            <div class="job-detail-main">
                <h2 class="job-block-title">About the role</h2>
                <div class="job-description">
                    @if (str_contains($opening->description, '<'))
                        {!! $opening->description !!}
                    @else
                        {!! nl2br(e($opening->description)) !!}
                    @endif
                </div>

                @if ($opening->company->description || $opening->company->website)
                    <div class="company-card">
                        <div class="company-card-head">
                            <h3>About {{ $opening->company->name }}</h3>
                        </div>
                        @if ($opening->company->description)
                            <div class="job-description">
                                @if (str_contains($opening->company->description, '<'))
                                    {!! $opening->company->description !!}
                                @else
                                    {!! nl2br(e($opening->company->description)) !!}
                                @endif
                            </div>
                        @endif
                        @if ($opening->company->website)
                            <a class="tool-link" href="{{ $opening->company->website }}" target="_blank" rel="noopener">Visit website →</a>
                        @endif
                    </div>
                @endif
            </div>

            <aside class="job-detail-aside">
                <div class="form-card job-apply-card">
                    <h2 class="form-title">Apply for this job</h2>
                    <p class="form-sub">Upload your CV and any supporting documents.</p>

                    @if (session('applied'))
                        <div class="success-box">{{ session('applied') }}</div>
                    @elseif (! $opening->is_accepting)
                        <div class="error-box">This position is no longer accepting applications.</div>
                    @else
                        @if ($errors->any())
                            <div class="error-box">Please fix the highlighted fields below and try again.</div>
                        @endif

                        <form method="POST" action="{{ route('jobs.apply', $opening) }}" enctype="multipart/form-data" class="job-apply-form">
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
                                <textarea id="cover_letter" name="cover_letter" rows="4" placeholder="Tell {{ $opening->company->name }} why you're a great fit…">{{ old('cover_letter') }}</textarea>
                                @error('cover_letter') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="cv">CV / Résumé <span class="req">*</span> <small>(PDF or Word, max 4 MB)</small></label>
                                <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" required>
                                @error('cv') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="field-label" for="documents">Supporting Documents <small>(optional, up to 5)</small></label>
                                <input type="file" id="documents" name="documents[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple>
                                @error('documents') <div class="field-error">{{ $message }}</div> @enderror
                                @error('documents.*') <div class="field-error">{{ $message }}</div> @enderror
                            </div>
                            <button type="submit" class="btn btn-brand" style="width:100%;">Submit Application</button>
                        </form>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
