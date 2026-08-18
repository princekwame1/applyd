<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Delete from a data table — one row, or every ticked row.
 *
 * Written once and shared because the dangerous parts are the same everywhere:
 * ask before doing it, check the permission **server-side**, refuse rows that
 * would take real work down with them, and clean up any file on disk that the
 * database will not.
 *
 * A table opts in by using this trait and overriding what differs — the noun in
 * the message, the permission, which rows are protected, what else has to go.
 * Nothing here is automatic beyond the delete itself.
 */
trait WithRowDelete
{
    /* ----------------------------------------------------- what a table sets */

    /** Permission required. Null falls back to the admin/super role check. */
    protected function deleteAbility(): ?string
    {
        return null;
    }

    /** Singular noun for the messages: "schedule", "tool", "response". */
    protected function deleteNoun(): string
    {
        return 'row';
    }

    /** How one row is named in the confirmation. */
    protected function deleteLabel(Model $row): string
    {
        return $row->name ?? $row->title ?? $this->deleteNoun().' #'.$row->getKey();
    }

    /** Extra warning under the question — what else goes, what is kept. */
    protected function deleteWarning(): string
    {
        return 'This cannot be undone.';
    }

    /**
     * Why this particular row may not be deleted, or null when it may.
     *
     * This is where a rule that already lives in a controller gets repeated for
     * the bulk path — a "refuse while it has responses" guard is worthless if
     * ticking the row and pressing Delete goes around it.
     */
    protected function deleteBlockedReason(Model $row): ?string
    {
        return null;
    }

    /**
     * Anything the database will not do: files on disk, rows that should be
     * detached rather than cascaded. Runs immediately before the delete.
     */
    protected function beforeDelete(Model $row): void {}

    /* ------------------------------------------------------------ the wiring */

    public function canDeleteRows(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $this->deleteAbility()
            ? $user->can($this->deleteAbility())
            : $user->hasAnyRole(['admin', 'super']);
    }

    /**
     * The ids a pending "delete selected" is holding.
     *
     * A bulk action has to go through the server to raise its dialog, and that
     * round-trip rebuilds the component — including its tick boxes. Copying the
     * ids here first means the answer is applied to what was actually ticked,
     * not to whatever the selection looks like a re-render later.
     */
    public array $pendingDeleteIds = [];

    public function deleteSelected(): void
    {
        $this->pendingDeleteIds = array_map('intval', $this->getSelected());
        $count = count($this->pendingDeleteIds);

        if (! $count) {
            $this->deleteToast(false, 'Tick at least one '.$this->deleteNoun().' first');

            return;
        }

        $this->confirmDelete(
            'Delete '.$count.' '.str($this->deleteNoun())->plural($count).'?',
            $this->deleteWarning(),
            'performDeleteSelected',
        );
    }

    public function performDelete(int $id): void
    {
        // Livewire methods are callable over HTTP by anyone who can reach the
        // component, so the gate is here — not only on the page it renders on.
        abort_unless($this->canDeleteRows(), 403);

        $row = $this->findForDelete($id);

        if (! $row) {
            return;
        }

        if ($reason = $this->deleteBlockedReason($row)) {
            $this->deleteToast(false, $reason);

            return;
        }

        $label = $this->deleteLabel($row);

        $this->beforeDelete($row);
        $row->delete();

        $this->deleteToast(true, $label.' deleted');
    }

    public function performDeleteSelected(): void
    {
        abort_unless($this->canDeleteRows(), 403);

        $ids = $this->pendingDeleteIds ?: array_map('intval', $this->getSelected());

        $this->pendingDeleteIds = [];
        $this->clearSelected();

        if (! $ids) {
            return;
        }

        $deleted = 0;
        $blocked = [];

        foreach ($this->rowsForDelete($ids) as $row) {
            if ($reason = $this->deleteBlockedReason($row)) {
                $blocked[] = $reason;

                continue;
            }

            $this->beforeDelete($row);
            $row->delete();
            $deleted++;
        }

        $this->deleteToast($deleted > 0, $this->deleteSummary($deleted, $blocked));
    }

    /**
     * What actually happened. A refusal is never silent: a batch that half
     * worked has to say so, or a protected row looks like a deleted one.
     */
    protected function deleteSummary(int $deleted, array $blocked): string
    {
        $noun = str($this->deleteNoun())->plural($deleted);
        $message = $deleted.' '.$noun.' deleted';

        if ($blocked) {
            $message .= ' · '.count($blocked).' kept: '.$blocked[0];

            if (count($blocked) > 1) {
                $message .= ' (and '.(count($blocked) - 1).' more)';
            }
        }

        return $deleted ? $message : 'Nothing deleted — '.($blocked[0] ?? 'those rows are gone already');
    }

    /** @return Collection<int, Model> */
    protected function rowsForDelete(array $ids): Collection
    {
        return $this->deleteQuery()->whereIn($this->deleteKeyName(), $ids)->get();
    }

    protected function findForDelete(int $id): ?Model
    {
        return $this->deleteQuery()->where($this->deleteKeyName(), $id)->first();
    }

    /** The model the table is built on. Override if a table needs eager loads. */
    protected function deleteQuery()
    {
        $model = $this->getModel();

        return $model::query();
    }

    protected function deleteKeyName(): string
    {
        return (new ($this->getModel()))->getKeyName();
    }

    /**
     * SweetAlert2 confirm that calls back into the component on "yes" — the
     * project's confirmation convention, adapted for a Livewire action where
     * there is no form to hang `data-confirm` on.
     */
    protected function confirmDelete(string $title, string $text, string $onConfirm): void
    {
        $this->js(sprintf(
            "Swal.fire({title:'%s',text:'%s',icon:'warning',showCancelButton:true,confirmButtonColor:'#c73a41',cancelButtonColor:'#5f605f',confirmButtonText:'Yes, delete',cancelButtonText:'Cancel'}).then((r) => { if (r.isConfirmed) { \$wire.%s } })",
            addslashes($title),
            addslashes($text),
            $onConfirm,
        ));
    }

    protected function deleteToast(bool $success, string $message): void
    {
        $this->js(sprintf(
            "Swal.fire({toast:true,position:'top-end',showConfirmButton:false,timer:4500,timerProgressBar:true,icon:'%s',title:'%s'})",
            $success ? 'success' : 'error',
            addslashes($message),
        ));
    }
}
