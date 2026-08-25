@extends('layouts.app')

@section('title', 'Contact Applyd Academy')
@section('og_description', cms('contact', 'hero_sub') ?? '')

@section('content')
<section class="page-hero">
    <div class="container center">
        <span class="page-eyebrow">{{ cms('contact', 'hero_eyebrow') }}</span>
        <h1 class="section-title">{{ cms('contact', 'hero_title') }}</h1>
        <p class="section-lead">{{ cms('contact', 'hero_sub') }}</p>
    </div>
</section>

<section>
    <div class="container">
        <div class="grid grid-3 contact-grid">
            <div class="card contact-card">
                <h3>General Enquiries</h3>
                <p>{{ cms('contact', 'gen_text') }}</p>
                <a href="mailto:{{ cms('contact', 'gen_email') }}" class="contact-link">{{ cms('contact', 'gen_email') }}</a>
            </div>
            <div class="card contact-card">
                <h3>Support</h3>
                <p>{{ cms('contact', 'sup_text') }}</p>
                <a href="mailto:{{ cms('contact', 'sup_email') }}" class="contact-link">{{ cms('contact', 'sup_email') }}</a>
            </div>
            <div class="card contact-card">
                <h3>Visit Us</h3>
                <p>{!! cms_html('contact', 'visit_address') !!}</p>
                <a href="https://maps.google.com/?q={{ urlencode(cms('contact', 'visit_address')) }}" target="_blank" rel="noopener" class="contact-link">Get directions →</a>
            </div>
        </div>

    </div>
</section>

<section class="alt">
    <div class="container">
        <div class="form-card contact-form-wrap">
            <h2 class="section-title" style="text-align: center; margin-bottom: 30px;">{{ cms('contact', 'form_heading') }}</h2>
            @if (session('contact_success'))
                <div class="success-box" style="margin-bottom: 20px;">
                    Thanks for reaching out! We'll get back to you soon.
                </div>
            @endif

            <form method="POST" action="{{ route('contact.submit') }}" class="contact-form">
                @csrf
                <div class="form-group">
                    <label class="field-label" for="name">Your Name <span class="req">*</span></label>
                    <input type="text" id="name" name="name" placeholder="e.g. Ama Mensah" value="{{ old('name') }}" required>
                    @error('name') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="field-label" for="email">Your Email <span class="req">*</span></label>
                    <input type="email" id="email" name="email" placeholder="e.g. ama@example.com" value="{{ old('email') }}" required>
                    @error('email') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="field-label" for="subject">Subject <span class="req">*</span></label>
                    <input type="text" id="subject" name="subject" placeholder="e.g. Bootcamp inquiry" value="{{ old('subject') }}" required>
                    @error('subject') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="field-label" for="message">Message <span class="req">*</span></label>
                    <textarea id="message" name="message" rows="6" placeholder="Tell us what you need..." required>{{ old('message') }}</textarea>
                    @error('message') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; cursor: pointer;">Send Message</button>
            </form>
        </div>
    </div>
</section>
@endsection
