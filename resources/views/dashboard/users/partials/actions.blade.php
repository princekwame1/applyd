<div class="row-actions">
    <a href="{{ route('dashboard.users.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.users.edit', $id) }}" data-modal-title="Edit User"><i class="fa-solid fa-pen-to-square"></i></a>
    @if ($id !== auth()->id())
        <form method="POST" action="{{ route('dashboard.users.destroy', $id) }}" data-confirm="Delete this user? This cannot be undone.">
            @csrf
            @method('DELETE')
            <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
        </form>
    @endif
</div>
