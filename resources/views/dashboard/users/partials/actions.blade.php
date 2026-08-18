<div class="row-actions">
    <a href="{{ route('dashboard.users.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.users.edit', $id) }}" data-modal-title="Edit User"><i class="fa-solid fa-pen-to-square"></i></a>

    @php($target = App\Models\User::find($id))
    @if ($target && ! App\Support\Impersonation::blockedReason(auth()->user(), $target))
        <form method="POST" action="{{ route('dashboard.users.impersonate', $id) }}"
              data-confirm="View the site as {{ $target->name }}? You stay signed in as yourself underneath and can stop at any time.">
            @csrf
            <button type="submit" title="View as this user" aria-label="View as this user"><i class="fa-solid fa-user-secret"></i></button>
        </form>
    @endif

    @if ($id !== auth()->id())
        <form method="POST" action="{{ route('dashboard.users.destroy', $id) }}" data-confirm="Delete this user? This cannot be undone.">
            @csrf
            @method('DELETE')
            <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
        </form>
    @endif
</div>
