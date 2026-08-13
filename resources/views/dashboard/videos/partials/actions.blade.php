<div class="row-actions">
    <a href="{{ route('dashboard.videos.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.videos.edit', $id) }}" data-modal-title="Edit Video"><i class="fa-solid fa-pen-to-square"></i></a>
    <form method="POST" action="{{ route('dashboard.videos.destroy', $id) }}" data-confirm="Delete this video? It will disappear from the website.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
