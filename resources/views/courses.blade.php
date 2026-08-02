@extends('layouts.app')

@section('title', 'Courses at Applyd Academy')

@section('content')
{{-- Tools You'll Learn --}}
@php
    $shortNames = config('bootcamp.category_short_names');
    $toolCount = $tools->count();
@endphp

<section class="alt contact-hero">
    <div class="container center">
        <h1 class="section-title">Job Opportunities</h1>
        <p class="section-lead">Explore the latest job openings in our community.</p>
    </div>
</section>
<section id="courses">
    <div class="container">
        <div class="center">
            {{-- <h2>Your Career Journey Starts Here</h2> --}}
            <p class="section-lead">Discover the perfect role for you in our community of professionals.</p>
            {{-- <div class="journey-pills">
                <span class="jp"><i>1</i> Choose your tools</span>
                <span class="jp"><i>2</i> Learn with experts</span>
                <span class="jp"><i>3</i> Apply your skills</span>
            </div> --}}
            <div class="filter-tabs" id="toolTabs">
                <button type="button" class="tab active" data-cat="all">All <em>{{ $toolCount }}</em></button>
                @foreach ($toolCategories as $category => $categoryTools)
                    <button type="button" class="tab" data-cat="{{ Str::slug($category) }}">{{ $shortNames[$category] ?? $category }} <em>{{ count($categoryTools) }}</em></button>
                @endforeach
            </div>
        </div>
        <div class="tool-grid">
            @foreach ($tools as $tool)
                <div class="card tool-item" data-cat="{{ Str::slug($tool->category) }}">
                    <div class="tool-thumb">
                        <img src="{{ $tool->image_url }}" alt="{{ $tool->name }}" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <h3>{{ $tool->name }}</h3>
                        <p>{{ $tool->blurb }}</p>
                        <a href="{{ route('landing') }}#register" class="btn btn-brand btn-sm tool-cta">Apply here</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Schedule --}}
{{-- <section id="schedule" class="alt">
    <div class="container">
        <h2 class="section-title">Your 24-Day Journey</h2>
        <div class="table-wrap">
            <table class="nice">
                <thead><tr><th>Week</th><th>Focus</th></tr></thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr><td>{{ $schedule->week_label }}</td><td>{{ $schedule->focus }}</td></tr>
                    @empty
                        <tr><td colspan="2" style="text-align:center; color:var(--ink-soft);">Schedule coming soon. Reserve your spot to be notified first.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section> --}}

{{-- Final CTA --}}
{{-- <section class="final-cta">
    <div class="container">
        <h2 class="section-title">No experience? No problem.</h2>
        <p class="section-lead" style="margin-left:auto; margin-right:auto;">You dream of a better career. We're the bridge. 24 days. 24 tools. Completely free.</p>
        <a href="{{ route('landing') }}#register" class="btn btn-primary">Reserve Your Free Spot →</a>
        <p class="micro">No cost. No experience required. Cancel anytime.</p>
    </div>
</section> --}}
@endsection

@push('scripts')
<script>
    $(function () {
        $('#toolTabs .tab').on('click', function () {
            $('#toolTabs .tab').removeClass('active');
            $(this).addClass('active');
            var cat = $(this).data('cat');
            $('.tool-item').each(function () {
                var match = cat === 'all' || $(this).data('cat') === cat;
                $(this).toggleClass('tool-hidden', !match);
            });
        });
    });
</script>
@endpush
