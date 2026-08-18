{{--
    Delete button for a rappasoft table row.

    The confirmation is raised **in the browser**, not by calling back into the
    component first. Doing it server-side meant a whole round-trip — and so a
    visible table re-render — before the dialog even appeared, and left a window
    where the component's state had already been rebuilt underneath the pending
    answer. One click, one dialog, one request, and only if you say yes.

    $id      — primary key of the row
    $title   — the question (optional)
    $text    — what happens (optional)
--}}
<button type="button" class="link-danger" title="Delete" aria-label="Delete"
        data-row-delete="{{ $id }}"
        data-row-delete-title="{{ $title ?? 'Delete this row?' }}"
        data-row-delete-text="{{ $text ?? 'This cannot be undone.' }}">
    <i class="fa-solid fa-trash"></i>
</button>
