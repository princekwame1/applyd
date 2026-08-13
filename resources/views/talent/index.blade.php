@extends('layouts.app')

@section('title', 'Drop Your CV — Applyd Academy')

@section('content')
<section class="page-hero">
    <div class="container center">
        <span class="page-eyebrow">Talent Pool</span>
        <h1 class="section-title">Nothing open that fits? Leave your CV anyway.</h1>
        <p class="section-lead">
            Tell us the kind of work you're after and we'll keep your CV on file. When an employer posts a job
            in one of your sectors, you're already in the room.
        </p>
    </div>
</section>

<section>
    <div class="container talent-wrap">
        <div class="talent-side">
            <div class="card talent-note">
                <h3>How it works</h3>
                <ol class="talent-steps">
                    <li><span>1</span> Upload your CV and pick up to 5 sectors you want to work in.</li>
                    <li><span>2</span> Employers hiring in those sectors see your profile — first name only, no contact details.</li>
                    <li><span>3</span> When one wants to talk, they unlock your CV and reach out directly.</li>
                </ol>
                <p class="talent-privacy">
                    Your email, phone number and CV file stay hidden until an employer pays to open them.
                    We never publish them on the site.
                </p>
                @if ($openCount)
                    <p class="talent-stat"><b>{{ number_format($openCount) }}</b> {{ Str::plural('job', $openCount) }} open right now · <a href="{{ route('jobs') }}">browse them</a></p>
                @endif
            </div>
        </div>

        <div class="talent-main">
            <div class="form-card">
                <h2 class="form-title">Add your CV</h2>
                <p class="form-sub">Takes about a minute. No account needed.</p>

                @if (session('talent_status'))
                    <div class="success-box">{{ session('talent_status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="error-box">Please fix the highlighted fields below and try again.</div>
                @endif

                <form method="POST" action="{{ route('talent.store') }}" enctype="multipart/form-data" class="job-apply-form">
                    @csrf

                    <div class="form-row">
                        <label class="field-label" for="full_name">Full Name <span class="req">*</span></label>
                        <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required>
                        @error('full_name') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-row">
                        <label class="field-label" for="email">Email <span class="req">*</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                        @error('email') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-row">
                        <label class="field-label" for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}">
                        @error('phone') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-row">
                        <label class="field-label" for="headline">What you do <small>(one line)</small></label>
                        <input type="text" id="headline" name="headline" value="{{ old('headline') }}" placeholder="e.g. Social media manager, 3 years">
                        @error('headline') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-row">
                        <label class="field-label" for="location">Where you're based</label>
                        <input type="text" id="location" name="location" value="{{ old('location') }}" placeholder="e.g. Accra, Ghana">
                        @error('location') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-row">
                        <label class="field-label" for="sectors">Sectors you want to work in <span class="req">*</span> <small>(up to 5)</small></label>
                        <select id="sectors" name="sectors[]" multiple required>
                            @foreach ($sectors as $sector)
                                <option value="{{ $sector }}" @selected(in_array($sector, (array) old('sectors', [])))>{{ $sector }}</option>
                            @endforeach
                        </select>
                        @error('sectors') <div class="field-error">{{ $message }}</div> @enderror
                        @error('sectors.*') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-row">
                        <label class="field-label" for="summary">Anything else <small>(optional)</small></label>
                        <textarea id="summary" name="summary" rows="4" placeholder="A few lines about what you're looking for.">{{ old('summary') }}</textarea>
                        @error('summary') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-row">
                        <label class="field-label" for="cv">Your CV <span class="req">*</span> <small>(PDF or Word, max 4 MB)</small></label>
                        <input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx" required>
                        @error('cv') <div class="field-error">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="btn btn-brand">Add my CV</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
