<?php

namespace App\Livewire;

use App\Models\Schedule;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class SchedulesTable extends DataTableComponent
{
    protected $model = Schedule::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('sort_order', 'asc');
        $this->setSearchDisabled();
        $this->setPaginationDisabled();
        $this->setDefaultReorderSort('sort_order', 'asc');
        $this->setReorderEnabled();
    }

    public function reorder($rows): void
    {
        foreach ($rows as $row) {
            Schedule::where('id', $row['id'])->update(['sort_order' => (int) $row['sort_order']]);
        }
    }

    public function columns(): array
    {
        return [
            Column::make('Week', 'week_label')
                ->sortable(),
            Column::make('Focus', 'focus'),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.schedules.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }
}
