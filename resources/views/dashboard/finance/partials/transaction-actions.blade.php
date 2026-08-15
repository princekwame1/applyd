<div class="row-actions">
    <a href="{{ route('dashboard.finance.transaction', $transaction) }}" title="Open" aria-label="Open"><i class="fa-solid fa-eye"></i></a>

    <a href="{{ route('dashboard.finance.edit', $transaction) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.finance.edit', $transaction) }}" data-modal-title="Edit entry"><i class="fa-solid fa-pen-to-square"></i></a>

    <form method="POST" action="{{ route('dashboard.finance.destroy', $transaction) }}"
          data-confirm="Delete this entry and any invoice or receipt attached to it? This can't be undone.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
