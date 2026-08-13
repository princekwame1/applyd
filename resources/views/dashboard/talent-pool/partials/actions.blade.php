<div class="row-actions">
    <a href="{{ route('dashboard.talent-pool.show', $id) }}" title="View" aria-label="View"
       data-modal-open data-modal-url="{{ route('dashboard.talent-pool.show', $id) }}" data-modal-title="Candidate"><i class="fa-solid fa-eye"></i></a>
    <a href="{{ route('dashboard.talent-pool.cv', $id) }}" title="Download CV" aria-label="Download CV"><i class="fa-solid fa-file-arrow-down"></i></a>
    <form method="POST" action="{{ route('dashboard.talent-pool.destroy', $id) }}"
          data-confirm="Delete this candidate and their CV file? This cannot be undone.">
        @csrf
        @method('DELETE')
        <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
    </form>
</div>
