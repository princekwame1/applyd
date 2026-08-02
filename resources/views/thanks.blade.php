@extends('layouts.app')

@section('title', "You're Registered! — Digital Tools Bootcamp 2026")

@section('content')
<div class="thanks-wrap">
    <div class="thanks-card">
        <div class="big">🎉</div>
        <h1 class="section-title">Thank you for registering, {{ $firstName }}!</h1>
        <p class="section-lead" style="margin: 0 auto 8px;">You're in for the <strong>Digital Tools Bootcamp 2026</strong>. Check your email and phone for your confirmation and session details. We can't wait to have you with us.</p>

        <div class="socials">
            @foreach ($socialLinks as $platform => $url)
                <a class="social-btn" href="{{ $url }}" target="_blank" rel="noopener">
                    @switch($platform)
                        @case('TikTok') 🎵 @break
                        @case('Instagram') 📸 @break
                        @case('LinkedIn') 💼 @break
                        @case('Facebook') 👍 @break
                    @endswitch
                    {{ $platform }}
                </a>
            @endforeach
        </div>

        <p style="margin-top: 32px;"><a href="{{ route('landing') }}">← Back to the bootcamp page</a></p>
    </div>
</div>
@endsection
