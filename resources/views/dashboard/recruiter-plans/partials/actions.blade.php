<div class="row-actions">
    <a href="{{ route('dashboard.recruiter-plans.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.recruiter-plans.edit', $id) }}" data-modal-title="Edit Plan"><i class="fa-solid fa-pen-to-square"></i></a>
    <form method="POST" action="{{ route('dashboard.recruiter-plans.destroy', $id) }}"
          data-confirm="Delete this plan? Recruiters stop seeing it. Credits already bought on it are kept.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
