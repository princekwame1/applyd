<div style="display:flex; gap:10px; align-items:center;">
    <a href="{{ route('dashboard.schedules.edit', $id) }}">Edit</a>
    <form method="POST" action="{{ route('dashboard.schedules.destroy', $id) }}" data-confirm="Delete this schedule entry?">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger">Delete</button>
    </form>
</div>
