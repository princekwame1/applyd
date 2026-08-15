<div class="row-actions">
    <a href="{{ route('dashboard.questionnaires.build', $questionnaire) }}"
       title="Questions" aria-label="Questions"><i class="fa-solid fa-list-check"></i></a>

    <a href="{{ route('dashboard.questionnaires.responses', $questionnaire) }}"
       title="Responses" aria-label="Responses"><i class="fa-solid fa-chart-simple"></i></a>

    <a href="{{ route('dashboard.questionnaires.edit', $questionnaire) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.questionnaires.edit', $questionnaire) }}" data-modal-title="Edit Form"><i class="fa-solid fa-pen-to-square"></i></a>

    @if ($questionnaire->is_published)
        <a href="{{ route('forms.show', $questionnaire) }}" target="_blank" rel="noopener"
           title="Open public form" aria-label="Open public form"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
    @endif

    <form method="POST" action="{{ route('dashboard.questionnaires.duplicate', $questionnaire) }}"
          data-confirm="Copy this form and its questions into a new, unpublished form? Responses are not copied.">
        @csrf
        <button type="submit" title="Duplicate" aria-label="Duplicate"><i class="fa-regular fa-copy"></i></button>
    </form>

    <form method="POST" action="{{ route('dashboard.questionnaires.destroy', $questionnaire) }}"
          data-confirm="Delete this form and its questions? Only possible while it has no responses.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
