<div class="row-actions">
    <a href="{{ route('dashboard.finance.categories.edit', $category) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.finance.categories.edit', $category) }}" data-modal-title="Edit category"><i class="fa-solid fa-pen-to-square"></i></a>

    <a href="{{ route('dashboard.finance', ['filters' => ['category' => $category->id]]) }}"
       title="See its entries" aria-label="See its entries"><i class="fa-solid fa-list"></i></a>

    <form method="POST" action="{{ route('dashboard.finance.categories.destroy', $category) }}"
          data-confirm="Delete this category? Only possible while nothing is filed under it.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
