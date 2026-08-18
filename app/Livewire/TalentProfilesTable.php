<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\JobOpening;
use App\Models\TalentProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class TalentProfilesTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = TalentProfile::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);

        // `sectors` sits behind a label column, and rappasoft only selects
        // fields behind real columns.
        $this->setAdditionalSelects(['talent_profiles.sectors']);
    }

    public function filters(): array
    {
        $sectors = ['' => 'All sectors'];
        foreach (JobOpening::SECTORS as $sector) {
            $sectors[$sector] = $sector;
        }

        return [
            SelectFilter::make('Sector')
                ->options($sectors)
                ->filter(fn (Builder $builder, string $value) => $builder->whereJsonContains('sectors', $value)),
            SelectFilter::make('Availability')
                ->options(['' => 'All', '1' => 'Open to work', '0' => 'Paused'])
                ->filter(fn (Builder $builder, string $value) => $builder->where('is_available', (bool) $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Candidate', 'full_name')
                ->sortable()
                ->searchable(),
            Column::make('Email', 'email')
                ->sortable()
                ->searchable(),
            Column::make('Headline', 'headline')
                ->searchable()
                ->format(fn ($value) => e($value ?: '—')),
            Column::make('Sectors')
                ->label(fn ($row) => e(implode(' · ', array_slice($row->sectorList(), 0, 2)))
                    .(count($row->sectorList()) > 2
                        ? ' <span style="color:var(--ink-soft);">+'.(count($row->sectorList()) - 2).'</span>'
                        : ''))
                ->html(),
            Column::make('Unlocks')
                ->label(fn ($row) => number_format($row->unlocks()->count())),
            Column::make('Status', 'is_available')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Open to work</span>'
                    : '<span class="badge badge-no">Paused</span>')
                ->html(),
            Column::make('Dropped', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y')),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.talent-pool.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteNoun(): string
    {
        return 'candidate';
    }

    protected function deleteLabel(Model $row): string
    {
        return $row->full_name;
    }

    protected function deleteWarning(): string
    {
        return 'Their CV is deleted from the server too. Companies that already unlocked them keep what they were shown.';
    }

    protected function beforeDelete(Model $row): void
    {
        if ($row->cv_path) {
            Storage::disk('local')->delete($row->cv_path);
        }
    }
}
