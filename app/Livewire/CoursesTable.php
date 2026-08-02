<?php

namespace App\Livewire;

use App\Models\Course;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class CoursesTable extends DataTableComponent
{
    protected $model = Course::class;

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
                ->format(fn ($value, $row) => $row->image_url
                    ? '<img src="'.$row->image_url.'" alt="" style="width:52px;height:36px;object-fit:cover;border-radius:6px;">'
                    : '<span style="color:var(--ink-soft);font-size:.8rem;">—</span>')
                ->html(),
            Column::make('Order', 'sort_order')
                ->sortable(),
            Column::make('Title', 'title')
                ->sortable()
                ->searchable(),
            Column::make('Level', 'level')
                ->sortable(),
            Column::make('Duration', 'duration'),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.courses.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }
}
