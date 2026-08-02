<div style="display:flex; gap:10px; align-items:center;">
    <a href="{{ route('dashboard.courses.edit', $id) }}">Edit</a>
    <form method="POST" action="{{ route('dashboard.courses.destroy', $id) }}" data-confirm="Delete this course?">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger">Delete</button>
    </form>
</div>
