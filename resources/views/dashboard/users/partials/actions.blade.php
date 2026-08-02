<div style="display:flex; gap:10px; align-items:center;">
    <a href="{{ route('dashboard.users.edit', $id) }}">Edit</a>
    @if ($id !== auth()->id())
        <form method="POST" action="{{ route('dashboard.users.destroy', $id) }}" data-confirm="Delete this user? This cannot be undone.">
            @csrf
            @method('DELETE')
            <button type="submit" class="link-danger">Delete</button>
        </form>
    @endif
</div>
