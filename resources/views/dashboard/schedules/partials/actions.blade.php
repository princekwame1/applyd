<div class="row-actions">
    <a href="{{ route('dashboard.schedules.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.schedules.edit', $id) }}" data-modal-title="Edit Schedule Entry"><i class="fa-solid fa-pen-to-square"></i></a>
    <form method="POST" action="{{ route('dashboard.schedules.destroy', $id) }}" data-confirm="Delete this schedule entry?">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
