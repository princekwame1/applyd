<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Spatie\Permission\Models\Role;

class RolesTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = Role::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('name', 'asc');
        $this->setSearchDisabled();
        $this->setPaginationDisabled();
    }

    public function builder(): Builder
    {
        return Role::query()->with('permissions')->select('roles.*');
    }

    public function columns(): array
    {
        return [
            Column::make('Role', 'name')
                ->sortable()
                ->format(fn ($value) => '<strong>'.e(ucfirst($value)).'</strong>')
                ->html(),
            Column::make('Permissions')
                ->label(function ($row) {
                    if ($row->permissions->isEmpty()) {
                        return '<span style="color:var(--ink-soft);font-size:.85rem;">None</span>';
                    }

                    return $row->permissions->pluck('name')
                        ->map(fn ($p) => '<span class="tag" style="font-size:.72rem;padding:3px 10px;">'.e($p).'</span>')
                        ->implode(' ');
                })
                ->html(),
            Column::make('Users')
                ->label(fn ($row) => $row->users()->count()),
            Column::make('Actions')
                ->label(fn ($row) => view('dashboard.roles.partials.actions', ['id' => $row->id, 'name' => $row->name])->render())
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteAbility(): ?string
    {
        return 'manage roles';
    }

    protected function deleteNoun(): string
    {
        return 'role';
    }

    protected function deleteWarning(): string
    {
        return 'Only possible for a role nobody holds.';
    }

    /** Mirrors RoleController::destroy — the bulk path must not go around it. */
    protected function deleteBlockedReason(Model $row): ?string
    {
        if ($row->name === 'super') {
            return 'the super role cannot be deleted';
        }

        return $row->users()->count()
            ? $row->name.' is assigned to someone — reassign them first'
            : null;
    }
}
