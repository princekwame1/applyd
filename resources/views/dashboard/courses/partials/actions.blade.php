<div class="row-actions">
    <a href="{{ route('dashboard.courses.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.courses.edit', $id) }}" data-modal-title="Edit Course"><i class="fa-solid fa-pen-to-square"></i></a>
    <form method="POST" action="{{ route('dashboard.courses.destroy', $id) }}" data-confirm="Delete this course?">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
