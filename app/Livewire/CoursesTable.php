<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\Course;
use App\Models\CourseEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class CoursesTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = Course::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
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
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.courses.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteAbility(): ?string
    {
        return 'manage courses';
    }

    protected function deleteNoun(): string
    {
        return 'course';
    }

    protected function deleteWarning(): string
    {
        return 'Only possible while nothing is registered on it.';
    }

    /**
     * Registrations are money and people. `course_enrollments.course_id` is
     * nullOnDelete, so the rows would survive — but orphaned, with no way to
     * tell what anybody paid for.
     */
    protected function deleteBlockedReason(Model $row): ?string
    {
        $count = CourseEnrollment::where('course_id', $row->id)->count();

        return $count
            ? $row->title.' has '.$count.' registration(s) — archive it instead'
            : null;
    }

    protected function beforeDelete(Model $row): void
    {
        if ($row->image) {
            Storage::disk('public')->delete($row->image);
        }
    }
}
