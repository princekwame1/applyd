<div style="display:flex; gap:10px; align-items:center;">
    <a href="{{ route('dashboard.roles.edit', $id) }}">Edit</a>
    @if ($name !== 'super')
        <form method="POST" action="{{ route('dashboard.roles.destroy', $id) }}" data-confirm="Delete the '{{ $name }}' role?">
            @csrf
            @method('DELETE')
            <button type="submit" class="link-danger">Delete</button>
        </form>
    @endif
</div>
