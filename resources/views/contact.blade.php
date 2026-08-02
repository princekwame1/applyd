@extends('layouts.app')

@section('title', 'Contact Us — Applyd Academy')

@section('content')
<section class="alt contact-hero">
    <div class="container center">
        <h1 class="section-title">Contact Us</h1>
        <p class="section-lead">Questions about the bootcamp, partnerships, or anything else? We'd love to hear from you.</p>
    </div>
</section>

<section >
    <div class="container">
        <div class="grid grid-3 contact-grid">
            <div class="card contact-card">
                {{-- <span class="icon">📧</span> --}}
                <h3>General Enquiries</h3>
                <p>For programme info, partnerships, and media.</p>
                <a href="mailto:info@applydacademy.com" class="contact-link">info@applydacademy.com</a>
            </div>
            <div class="card contact-card">
                {{-- <span class="icon">🛟</span> --}}
                <h3>Support</h3>
                <p>Registration issues, session access, or technical help.</p>
                <a href="mailto:support@applydacademy.com" class="contact-link">support@applydacademy.com</a>
            </div>
            <div class="card contact-card">
                {{-- <span class="icon">📍</span> --}}
                <h3>Visit Us</h3>
                <p>Trade Fair, 25 Giffard Rd,<br>Accra, Ghana</p>
                <a href="https://maps.google.com/?q=Trade+Fair,+25+Giffard+Rd,+Accra" target="_blank" rel="noopener" class="contact-link">Get directions →</a>
            </div>
        </div>

     
    </div>
       <div class="map-wrap">
            <iframe
                src="https://maps.google.com/maps?q=Trade%20Fair%2C%2025%20Giffard%20Rd%2C%20Accra&z=16&output=embed"
                width="100%"
                height="420"
                style="border:0;"
                allowfullscreen
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                title="Applyd Academy — Trade Fair, 25 Giffard Rd, Accra">
            </iframe>
        </div>
</section>
@endsection
