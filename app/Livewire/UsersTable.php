<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class UsersTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = User::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('name', 'asc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);
    }

    public function builder(): Builder
    {
        return User::query()->with('roles');
    }

    public function columns(): array
    {
        return [
            Column::make('User', 'name')
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    $avatar = $row->avatar_url
                        ? '<img src="'.$row->avatar_url.'" alt="" style="width:32px;height:32px;object-fit:cover;border-radius:50%;">'
                        : '<span style="display:inline-flex;width:32px;height:32px;border-radius:50%;background:var(--brand);color:#fff;font-weight:800;font-size:.8rem;align-items:center;justify-content:center;">'.strtoupper(substr($value, 0, 1)).'</span>';

                    return '<div style="display:flex;align-items:center;gap:10px;">'.$avatar.'<strong>'.e($value).'</strong></div>';
                })
                ->html(),
            Column::make('Email', 'email')
                ->sortable()
                ->searchable(),
            Column::make('Roles', 'id')
                ->format(fn ($value, $row) => e($row->role_label)),
            Column::make('Created', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y')),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.users.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteAbility(): ?string
    {
        return 'manage users';
    }

    protected function deleteNoun(): string
    {
        return 'user';
    }

    protected function deleteWarning(): string
    {
        return 'The account and its access go for good. Anything they created stays.';
    }

    /**
     * Two accounts must survive any tidy-up: your own (locking yourself out of
     * the dashboard mid-click), and the last `super`, which is the only role
     * that can hand out roles again afterwards.
     */
    protected function deleteBlockedReason(Model $row): ?string
    {
        if ($row->id === auth()->id()) {
            return 'you cannot delete your own account';
        }

        if ($row->hasRole('super') && User::role('super')->count() <= 1) {
            return $row->name.' is the last super account';
        }

        return null;
    }
}
