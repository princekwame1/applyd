<?php

namespace App\Livewire;

use App\Http\Controllers\Dashboard\DigitalProductController;
use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\DigitalProduct;
use App\Support\ProductFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class DigitalProductsTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = DigitalProduct::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('sort_order', 'asc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);
        $this->setDefaultReorderSort('sort_order', 'asc');
        $this->setReorderEnabled();

        // The cover and delivery columns read fields that sit behind no column
        // of their own — rappasoft only selects what a column names.
        $this->setAdditionalSelects([
            'digital_products.cover',
            'digital_products.file_path',
            'digital_products.file_name',
            'digital_products.external_url',
        ]);
    }

    public function builder(): Builder
    {
        return DigitalProduct::query()
            ->withCount(['orders as sales_count' => fn ($q) => $q->where('status', 'paid')]);
    }

    public function reorder($rows): void
    {
        foreach ($rows as $row) {
            DigitalProduct::where('id', $row['id'])->update(['sort_order' => (int) $row['sort_order']]);
        }
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status')
                ->options(['' => 'All', '1' => 'On sale', '0' => 'Hidden'])
                ->filter(fn (Builder $builder, string $value) => $builder->where('is_published', (bool) $value)),
            SelectFilter::make('Delivery')
                ->options(['' => 'All', 'file' => 'File download', 'link' => 'External link'])
                ->filter(fn (Builder $builder, string $value) => $value === 'file'
                    ? $builder->whereNotNull('file_path')
                    : $builder->whereNull('file_path')),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Cover', 'title')
                ->format(fn ($value, $row) => $row->cover_url
                    ? '<img src="'.e($row->cover_url).'" alt="" style="width:52px;height:38px;object-fit:cover;border-radius:6px;">'
                    : '<span style="color:var(--ink-soft);">—</span>')
                ->html(),
            Column::make('Product', 'title')
                ->sortable()
                ->searchable(),
            Column::make('Category', 'category')
                ->sortable()
                ->searchable()
                ->format(fn ($value) => e($value ?: '—')),
            Column::make('Price', 'price')
                ->sortable()
                ->format(fn ($value, $row) => e($row->price_label)),
            Column::make('Delivery', 'format')
                ->format(fn ($value, $row) => $row->deliversFile()
                    ? '<span class="badge badge-yes">File</span>'
                    : '<span class="badge badge-no">Link</span>')
                ->html(),
            Column::make('Sold', 'id')
                ->format(fn ($value, $row) => number_format($row->sales_count ?? 0)),
            Column::make('Status', 'is_published')
                ->sortable()
                ->format(fn ($value) => $value
                    ? '<span class="badge badge-yes">On sale</span>'
                    : '<span class="badge badge-no">Hidden</span>')
                ->html(),
            Column::make('Actions', 'id')
                ->format(fn ($value) => view('dashboard.products.partials.actions', ['id' => $value]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteNoun(): string
    {
        return 'product';
    }

    protected function deleteWarning(): string
    {
        return 'The file goes with it. Anything that has been bought is kept — unpublish those instead.';
    }

    /** The controller's rule, repeated for the path that goes around it. */
    protected function deleteBlockedReason(Model $row): ?string
    {
        return DigitalProductController::blockedReason($row);
    }

    /** The rows cascade; the file on disk and the cover image do not. */
    protected function beforeDelete(Model $row): void
    {
        ProductFiles::purge($row);
    }
}
