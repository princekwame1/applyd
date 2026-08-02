<?php

namespace App\Livewire;

use App\Models\Tool;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class ToolsTable extends DataTableComponent
{
    protected $model = Tool::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('sort_order', 'asc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);
    }

    public function columns(): array
    {
        return [
            Column::make('Image', 'image')
                ->format(fn ($value, $row) => '<img src="'.$row->image_url.'" alt="" style="width:52px;height:36px;object-fit:cover;border-radius:6px;">')
                ->html(),
            Column::make('Order', 'sort_order')
                ->sortable(),
            Column::make('Name', 'name')
                ->sortable()
                ->searchable(),
            Column::make('Category', 'category')
                ->sortable()
                ->searchable(),
            Column::make('Blurb', 'blurb'),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.tools.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }
}
