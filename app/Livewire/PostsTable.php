<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class PostsTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = Post::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('published_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);
        $this->setBulkActionsEnabled();
    }

    public function builder(): Builder
    {
        return Post::query()->with('category');
    }

    public function filters(): array
    {
        $options = ['' => 'All categories'];
        foreach (BlogCategory::orderBy('name')->get() as $cat) {
            $options[$cat->id] = $cat->name;
        }

        return [
            SelectFilter::make('Category')
                ->options($options)
                ->filter(fn (Builder $builder, string $value) => $builder->where('blog_category_id', $value)),
            SelectFilter::make('Status')
                ->options(['' => 'All', '1' => 'Published', '0' => 'Draft'])
                ->filter(fn (Builder $builder, string $value) => $builder->where('is_published', (bool) $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Cover', 'cover_image')
                ->format(fn ($value, $row) => $row->cover_image_url
                    ? '<img src="'.$row->cover_image_url.'" alt="" style="width:52px;height:36px;object-fit:cover;border-radius:6px;">'
                    : '<span style="color:var(--ink-soft);font-size:.8rem;">—</span>')
                ->html(),
            Column::make('Title', 'title')
                ->sortable()
                ->searchable(),
            Column::make('Category', 'blog_category_id')
                ->format(fn ($value, $row) => e($row->category?->name ?? '—')),
            Column::make('Status', 'is_published')
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Published</span>'
                    : '<span class="badge badge-no">Draft</span>')
                ->html(),
            Column::make('Date', 'published_at')
                ->sortable()
                ->format(fn ($value, $row) => e($row->date_label)),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.blog.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteNoun(): string
    {
        return 'post';
    }

    protected function deleteWarning(): string
    {
        return 'The post and its cover image go for good, and any link to it starts 404ing.';
    }

    protected function beforeDelete(Model $row): void
    {
        if ($row->cover_image) {
            Storage::disk('public')->delete($row->cover_image);
        }
    }
}
