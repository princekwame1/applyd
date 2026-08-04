<?php

namespace App\Livewire;

use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class CoursesTable extends DataTableComponent
{
    protected $model = Course::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('sort_order', 'asc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);
        $this->setDefaultReorderSort('sort_order', 'asc');
        $this->setReorderEnabled();
        $this->setBulkActionsEnabled();
    }

    public function reorder($rows): void
    {
        foreach ($rows as $row) {
            Course::where('id', $row['id'])->update(['sort_order' => (int) $row['sort_order']]);
        }
    }

    public function bulkActions(): array
    {
        return [
            'deleteSelected' => 'Delete',
        ];
    }

    public function deleteSelected(): void
    {
        Course::whereIn('id', $this->getSelected())->get()->each(function (Course $course) {
            if ($course->image) {
                Storage::disk('public')->delete($course->image);
            }
            $course->delete();
        });

        $count = count($this->getSelected());
        $this->clearSelected();

        $this->js("Swal.fire({toast:true,position:'top-end',showConfirmButton:false,timer:2500,icon:'success',title:'".$count." course(s) deleted'})");
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Level')
                ->options([
                    '' => 'All levels',
                    'Beginner' => 'Beginner',
                    'Intermediate' => 'Intermediate',
                    'Advanced' => 'Advanced',
                    'All levels' => 'All levels',
                ])
                ->filter(fn (Builder $builder, string $value) => $builder->where('level', $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Image', 'image')
                ->format(fn ($value, $row) => $row->image_url
                    ? '<img src="'.$row->image_url.'" alt="" style="width:52px;height:36px;object-fit:cover;border-radius:6px;">'
                    : '<span style="color:var(--ink-soft);font-size:.8rem;">—</span>')
                ->html(),
            Column::make('Title', 'title')
                ->sortable()
                ->searchable(),
            Column::make('Level', 'level')
                ->sortable(),
            Column::make('Duration', 'duration'),
            Column::make('Price', 'price')
                ->sortable()
                ->format(fn ($value, $row) => e($row->price_label)),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.courses.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }
}
