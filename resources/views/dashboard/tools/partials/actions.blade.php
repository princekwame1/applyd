<div class="row-actions">
    <a href="{{ route('dashboard.tools.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.tools.edit', $id) }}" data-modal-title="Edit Tool"><i class="fa-solid fa-pen-to-square"></i></a>
    <form method="POST" action="{{ route('dashboard.tools.destroy', $id) }}" data-confirm="Delete this tool? It will disappear from the landing page and registration form.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
