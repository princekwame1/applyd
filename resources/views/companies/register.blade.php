@extends('layouts.app')

@section('title', 'Post a Job — Applyd Academy')

@section('content')
<section class="page-hero">
    <div class="container center">
        <span class="page-eyebrow">For Employers</span>
        <h1 class="section-title">Hire from Our Community</h1>
        <p class="section-lead">Create a free company account, post your openings, and receive applications with CVs, all in one place.</p>
    </div>
</section>

<section>
    <div class="container">
        <div class="form-card" style="max-width: 640px;">
            <h2 class="form-title" style="font-size:1.3rem;">Company Sign Up</h2>
            <p class="form-sub">Already have an account? <a href="{{ route('login') }}">Log in</a></p>

            @if ($errors->any())
                <div class="error-box">Please fix the highlighted fields below and try again.</div>
            @endif

            <form method="POST" action="{{ route('companies.register.store') }}" style="display:grid; gap:14px;">
                @csrf
                <div>
                    <label class="field-label" for="company_name">Company Name <span class="req">*</span></label>
                    <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required>
                    @error('company_name') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-grid">
                    <div>
                        <label class="field-label" for="website">Website</label>
                        <input type="text" id="website" name="website" placeholder="https://…" value="{{ old('website') }}">
                        @error('website') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="field-label" for="location">Location</label>
                        <input type="text" id="location" name="location" placeholder="e.g. Accra, Ghana" value="{{ old('location') }}">
                        @error('location') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div>
                    <label class="field-label" for="description">About the Company</label>
                    <textarea id="description" name="description" rows="3" data-rich style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:1rem; font-family:inherit;">{{ old('description') }}</textarea>
                    @error('description') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="field-label" for="contact_name">Contact Person <span class="req">*</span></label>
                    <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required>
                    @error('contact_name') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="field-label" for="email">Work Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                    @error('email') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-grid">
                    <div>
                        <label class="field-label" for="password">Password <span class="req">*</span></label>
                        <input type="password" id="password" name="password" required>
                        @error('password') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="field-label" for="password_confirmation">Confirm Password <span class="req">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-brand" style="width:100%;">Create Company Account</button>
            </form>
        </div>
    </div>
</section>
@include('partials.quill')
@endsection
