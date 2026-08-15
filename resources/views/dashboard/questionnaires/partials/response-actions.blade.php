<div class="row-actions">
    <a href="{{ route('dashboard.questionnaires.response', $id) }}" title="View" aria-label="View"
       data-modal-open data-modal-url="{{ route('dashboard.questionnaires.response', $id) }}" data-modal-title="Response"><i class="fa-solid fa-eye"></i></a>

    <form method="POST" action="{{ route('dashboard.questionnaires.response.destroy', $id) }}"
          data-confirm="Delete this response? Any files uploaded with it are deleted too.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
