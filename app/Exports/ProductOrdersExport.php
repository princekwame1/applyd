<?php

namespace App\Exports;

use App\Models\ProductOrder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductOrdersExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return ProductOrder::query()->latest()->get();
    }

    public function headings(): array
    {
        return ['ID', 'Reference', 'Product', 'Buyer', 'Email', 'Phone', 'Amount', 'Payment Charge', 'Status', 'Paid At', 'Downloads', 'Last Download', 'Ordered At'];
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->reference,
            $order->product_title,
            $order->buyer_name,
            $order->buyer_email,
            $order->buyer_phone ?: '',
            (float) $order->amount,
            (float) $order->fee,
            ucfirst($order->status),
            $order->paid_at?->format('Y-m-d H:i') ?: '',
            $order->download_count,
            $order->last_downloaded_at?->format('Y-m-d H:i') ?: '',
            $order->created_at->format('Y-m-d H:i'),
        ];
    }
}
