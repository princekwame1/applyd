<div class="row-actions">
    <a href="{{ route('dashboard.blog.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.blog.edit', $id) }}" data-modal-title="Edit Post"><i class="fa-solid fa-pen-to-square"></i></a>
    <form method="POST" action="{{ route('dashboard.blog.destroy', $id) }}" data-confirm="Delete this post?">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
