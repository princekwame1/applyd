<?php

namespace App\Exports;

use App\Models\DigitalProduct;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DigitalProductsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return DigitalProduct::query()
            ->withCount(['orders as sales_count' => fn ($q) => $q->where('status', 'paid')])
            ->withSum(['orders as revenue' => fn ($q) => $q->where('status', 'paid')], 'amount')
            ->ordered()
            ->get();
    }

    public function headings(): array
    {
        return ['ID', 'Order', 'Product', 'Slug', 'Category', 'Format', 'Price', 'Delivery', 'Sold', 'Revenue', 'Status', 'Updated At'];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->sort_order,
            $product->title,
            $product->slug,
            $product->category ?: '',
            $product->format ?: '',
            (float) $product->price,
            $product->deliversFile() ? 'File download' : 'External link',
            $product->sales_count,
            (float) ($product->revenue ?? 0),
            $product->is_published ? 'On sale' : 'Hidden',
            $product->updated_at->format('Y-m-d H:i'),
        ];
    }
}
