@use('App\Support\Impersonation')

@if (Impersonation::active())
    @php($actor = Impersonation::impersonator())

    {{-- Fixed to the bottom so it survives scrolling: the way out has to be
         reachable from any page, including one that redirected here. --}}
    <div class="imp-bar" role="status">
        <span class="imp-bar-text">
            Viewing as <strong>{{ auth()->user()?->name }}</strong>
            @if ($actor) — signed in as {{ $actor->name }} @endif
        </span>

        <form method="POST" action="{{ route('impersonate.stop') }}">
            @csrf
            <button type="submit" class="imp-bar-stop">Stop impersonating</button>
        </form>

        {{-- The typeable way back, for when the button is not on screen: an
             error page renders no layout, so this banner is not there. Same
             address on any page of the site. --}}
        <span class="imp-bar-hint">or go to <code>{{ parse_url(route('impersonate.stop.get'), PHP_URL_PATH) }}</code></span>
    </div>
@endif
