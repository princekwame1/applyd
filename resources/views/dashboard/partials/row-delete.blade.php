{{--
    Delete button for a rappasoft table row. Livewire rather than a form,
    because the confirmation and the refusal both come back through the
    component (see App\Livewire\Concerns\WithRowDelete).

    $id — primary key of the row.
--}}
<button type="button" class="link-danger" title="Delete" aria-label="Delete"
        wire:click="deleteRow({{ $id }})" wire:loading.attr="disabled">
    <i class="fa-solid fa-trash"></i>
</button>
