<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithSkeletonLoader;
use App\Models\DigitalProduct;
use App\Models\ProductOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ProductOrdersTable extends DataTableComponent
{
    use Concerns\WithRowDelete;
    use WithSkeletonLoader;

    protected $model = ProductOrder::class;

    public function configure(): void
    {
        $this->configureSkeletonLoader();
        $this->setPrimaryKey('id');
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50]);
        $this->setPerPage(25);

        // The row actions build the buyer's download link from the token.
        $this->setAdditionalSelects(['product_orders.download_token']);
    }

    public function builder(): Builder
    {
        return ProductOrder::query()->with('product');
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status')
                ->options(['' => 'All', 'paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed'])
                ->filter(fn (Builder $builder, string $value) => $builder->where('status', $value)),
            SelectFilter::make('Product')
                ->options(
                    ['' => 'All'] + DigitalProduct::ordered()->pluck('title', 'id')->toArray()
                )
                ->filter(fn (Builder $builder, string $value) => $builder->where('digital_product_id', $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Buyer', 'buyer_name')
                ->sortable()
                ->searchable(),
            Column::make('Email', 'buyer_email')
                ->sortable()
                ->searchable(),
            // The snapshot, not the product's current title — this is what the
            // person actually bought.
            Column::make('Product', 'product_title')
                ->sortable()
                ->searchable(),
            Column::make('Amount', 'amount')
                ->sortable()
                ->format(fn ($value, $row) => e($row->amount_label)),
            Column::make('Status', 'status')
                ->sortable()
                ->format(fn ($value) => match ($value) {
                    'paid' => '<span class="badge badge-yes">Paid</span>',
                    'failed' => '<span class="badge badge-no">Failed</span>',
                    default => '<span class="badge badge-no">Pending</span>',
                })
                ->html(),
            Column::make('Downloads', 'download_count')
                ->sortable()
                ->format(fn ($value) => number_format((int) $value)),
            Column::make('Date', 'created_at')
                ->sortable()
                ->format(fn ($value) => $value->format('M j, Y g:ia')),
            Column::make('Actions', 'id')
                ->format(fn ($value, $row) => view('dashboard.product-orders.partials.actions', ['order' => $row]))
                ->html(),
        ];
    }

    public function bulkActions(): array
    {
        return ['deleteSelected' => 'Delete selected'];
    }

    protected function deleteNoun(): string
    {
        return 'order';
    }

    protected function deleteLabel(Model $row): string
    {
        return $row->product_title.' ('.$row->reference.')';
    }

    protected function deleteWarning(): string
    {
        return 'Abandoned and failed checkouts only — a paid order is the record of money taken and is kept.';
    }

    /**
     * A settled order is the receipt for a payment and the buyer's key to
     * their download. Clearing out abandoned checkouts is housekeeping;
     * deleting a paid one destroys both.
     */
    protected function deleteBlockedReason(Model $row): ?string
    {
        return $row->status === 'paid'
            ? $row->reference.' is paid — it is the record of that sale and the buyer\'s download'
            : null;
    }
}
