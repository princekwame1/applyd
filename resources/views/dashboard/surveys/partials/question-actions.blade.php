<div class="row-actions">
    <a href="{{ route('dashboard.surveys.questions.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.surveys.questions.edit', $id) }}" data-modal-title="Edit Question"><i class="fa-solid fa-pen-to-square"></i></a>
    <form method="POST" action="{{ route('dashboard.surveys.questions.destroy', $id) }}" data-confirm="Delete this question? It disappears from the public form. Answers already collected for it are kept but stop being shown.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
