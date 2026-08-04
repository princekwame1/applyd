<div class="row-actions">
    <a href="{{ route('dashboard.roles.edit', $id) }}" title="Edit" aria-label="Edit"
       data-modal-open data-modal-url="{{ route('dashboard.roles.edit', $id) }}" data-modal-title="Edit Role"><i class="fa-solid fa-pen-to-square"></i></a>
    @if ($name !== 'super')
        <form method="POST" action="{{ route('dashboard.roles.destroy', $id) }}" data-confirm="Delete the '{{ $name }}' role?">
            @csrf
            @method('DELETE')
            <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
        </form>
    @endif
</div>
