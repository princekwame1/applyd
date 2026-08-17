@use('App\Support\Whatsapp')

{{-- Hidden entirely when the number is cleared in the CMS, rather than
     rendering a button that opens a chat with nobody. --}}
@if (Whatsapp::enabled())
    @php($label = cms('site', 'whatsapp_label'))

    <a class="wa-float"
       href="{{ Whatsapp::link() }}"
       target="_blank"
       rel="noopener"
       aria-label="{{ $label }}">
        {{-- alt="" on purpose: the link already has an accessible name, and a
             second one would just be read out twice. --}}
        <img src="{{ asset('img/whatsapp.png') }}" alt="" width="56" height="56" decoding="async">
        <span class="wa-float-label" aria-hidden="true">{{ $label }}</span>
    </a>
@endif
