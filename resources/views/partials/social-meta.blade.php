{{--
    The single place Open Graph / Twitter Card tags are emitted — what Facebook,
    WhatsApp, LinkedIn, X and Slack read to build the preview card for a link.

    A page overrides any of these by defining the matching section:

        @section('og_image', $post->cover_image_url ?? '')
        @section('og_description', $post->display_excerpt ?? '')
        @section('og_type', 'article')

    Everything falls back to the site defaults, so a page that defines nothing
    still shares correctly. NEVER pass a value that can be null: Blade reads
    @section('x', null) as the OPENING of a block section, so it calls ob_start()
    and waits for an @endsection that never comes — the buffer leaks and the
    section stays undefined. Coalesce optional values to '' (a post with no
    cover image, a company with no logo) and let the fallback below do its job.

    Two things to know before editing:

    * og:image must be an ABSOLUTE url. asset() builds it from APP_URL, so
      APP_URL has to be right in production or every shared link points its
      preview at localhost.
    * Blade e()s the two-argument form of @section, so section content arrives
      ALREADY escaped. The fallbacks are escaped here to match and every value
      is printed with {!! !!} — escaped exactly once whichever branch wins.
      Printing them with {{ }} double-escapes and ships literal &amp;quot;.
--}}
@php
    $socialFallback = 'A free, hands-on learning experience: 24 digital tools, 24 expert facilitators, 3 countries, 24 days. 100% online and completely free.';

    $socialTitle = trim($__env->yieldContent('og_title'))
        ?: trim($__env->yieldContent('title', e('Applyd Academy')));

    $socialDescription = trim($__env->yieldContent('og_description')) ?: e($socialFallback);
    $socialDescription = preg_replace('/\s+/', ' ', $socialDescription);

    $socialImage = trim($__env->yieldContent('og_image')) ?: e(asset('img/og-default.jpg'));
    $socialType = trim($__env->yieldContent('og_type')) ?: 'website';
@endphp
<meta name="description" content="{!! $socialDescription !!}">

<meta property="og:site_name" content="Applyd Academy">
<meta property="og:type" content="{!! $socialType !!}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="{!! $socialTitle !!}">
<meta property="og:description" content="{!! $socialDescription !!}">
<meta property="og:image" content="{!! $socialImage !!}">
<meta property="og:image:alt" content="{!! $socialTitle !!}">
{{-- No og:image:width/height on purpose: a cover image is whatever the admin
     uploaded, and a wrong hint crops the preview worse than no hint at all. --}}

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{!! $socialTitle !!}">
<meta name="twitter:description" content="{!! $socialDescription !!}">
<meta name="twitter:image" content="{!! $socialImage !!}">
@stack('social-meta')
