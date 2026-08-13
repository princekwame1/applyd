<div class="row-actions">
    <a href="{{ route('dashboard.surveys.manage.edit', $survey) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.surveys.manage.edit', $survey) }}" data-modal-title="Edit Survey"><i class="fa-solid fa-pen-to-square"></i></a>

    <a href="{{ route('dashboard.surveys.questions', ['filters' => ['survey' => $survey->id]]) }}"
       title="Questions" aria-label="Questions"><i class="fa-solid fa-list-check"></i></a>

    <a href="{{ route('dashboard.surveys', ['survey' => $survey->slug]) }}"
       title="Results" aria-label="Results"><i class="fa-solid fa-chart-simple"></i></a>

    <form method="POST" action="{{ route('dashboard.surveys.manage.duplicate', $survey) }}"
          data-confirm="Copy this survey and its questions into a new, closed survey? Responses are not copied.">
        @csrf
        <button type="submit" title="Duplicate" aria-label="Duplicate"><i class="fa-regular fa-copy"></i></button>
    </form>

    <form method="POST" action="{{ route('dashboard.surveys.manage.destroy', $survey) }}"
          data-confirm="Delete this survey and its questions? Only possible while it has no responses.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
