<div class="row-actions">
    <a href="{{ route('dashboard.questionnaires.questions.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.questionnaires.questions.edit', $id) }}" data-modal-title="Edit Question"><i class="fa-solid fa-pen-to-square"></i></a>

    <form method="POST" action="{{ route('dashboard.questionnaires.questions.destroy', $id) }}"
          data-confirm="Delete this question? Answers already collected for it are kept.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
