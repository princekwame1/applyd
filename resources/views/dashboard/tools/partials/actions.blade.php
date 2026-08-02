<div style="display:flex; gap:10px; align-items:center;">
    <a href="{{ route('dashboard.tools.edit', $id) }}">Edit</a>
    <form method="POST" action="{{ route('dashboard.tools.destroy', $id) }}" data-confirm="Delete this tool? It will disappear from the landing page and registration form.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger">Delete</button>
    </form>
</div>
