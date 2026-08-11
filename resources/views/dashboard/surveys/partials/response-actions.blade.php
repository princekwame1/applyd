<div class="row-actions">
    <a href="{{ route('dashboard.surveys.response', $id) }}" title="View" aria-label="View"
       data-modal-open data-modal-url="{{ route('dashboard.surveys.response', $id) }}" data-modal-title="Response"><i class="fa-solid fa-eye"></i></a>
    <form method="POST" action="{{ route('dashboard.surveys.response.destroy', $id) }}" data-confirm="Delete this response? It is removed permanently and the totals above will change.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
