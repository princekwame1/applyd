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
    </div>
@endif
