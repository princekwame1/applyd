<?php

namespace App\Livewire;

use App\Models\SessionVideo;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class SessionVideosTable extends DataTableComponent
{
    use \App\Livewire\Concerns\WithSkeletonLoader;

    protected $model = SessionVideo::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('sort_order', 'asc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);
        $this->setDefaultReorderSort('sort_order', 'asc');
        $this->setReorderEnabled();

        // The thumb column renders `thumbnail_url`, which reads `thumbnail` —
        // rappasoft only selects fields behind real columns, so without this a
        // custom upload would never show here (only YouTube's own still).
        $this->setAdditionalSelects(['session_videos.thumbnail']);
    }

    public function reorder($rows): void
    {
        foreach ($rows as $row) {
            SessionVideo::where('id', $row['id'])->update(['sort_order' => (int) $row['sort_order']]);
        }
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status')
                ->options(['' => 'All', '1' => 'Published', '0' => 'Hidden'])
                ->filter(fn (Builder $builder, string $value) => $builder->where('is_published', (bool) $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Thumb', 'youtube_id')
                ->format(fn ($value, $row) => '<img src="'.e($row->thumbnail_url).'" alt="" style="width:64px;height:36px;object-fit:cover;border-radius:6px;">')
                ->html(),
            Column::make('Title', 'title')
                ->sortable()
                ->searchable(),
            Column::make('Session', 'session_label')
                ->sortable()
                ->searchable()
                ->format(fn ($value) => e($value ?: '—')),
            Column::make('Recorded', 'recorded_on')
                ->sortable()
                ->format(fn ($value, $row) => e($row->date_label ?? '—')),
            Column::make('Status', 'is_published')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">Published</span>'
                    : '<span class="badge badge-no">Hidden</span>')
                ->html(),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.videos.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }
}
