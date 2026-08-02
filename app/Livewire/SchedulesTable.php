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
    }

    public function columns(): array
    {
        return [
            Column::make('Order', 'sort_order')
                ->sortable(),
            Column::make('Week', 'week_label')
                ->sortable(),
            Column::make('Focus', 'focus'),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.schedules.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }
}
