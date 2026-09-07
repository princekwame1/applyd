<div class="row-actions">
    <a href="{{ route('dashboard.products.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.products.edit', $id) }}" data-modal-title="Edit Product"><i class="fa-solid fa-pen-to-square"></i></a>
    <form method="POST" action="{{ route('dashboard.products.destroy', $id) }}" data-confirm="Delete this product? The file goes with it. Anything already bought is kept.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
