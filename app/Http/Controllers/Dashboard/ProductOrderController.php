<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\ProductOrdersExport;
use App\Http\Controllers\Controller;
use App\Models\DigitalProduct;
use App\Models\ProductOrder;
use App\Services\EmailNotificationService;
use App\Services\ProductDeliveryService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * What has been sold. Two actions beyond looking:
 *
 * **Resend** re-sends the buyer their download link — the one thing that
 * actually goes wrong with a digital sale is the email not arriving, and the
 * link never expires, so resending is always safe.
 *
 * **Mark as paid** settles an order that was paid outside Paystack (a transfer,
 * cash at a workshop). It goes through exactly the same path as a card payment,
 * delivery email included, rather than inventing a second way to be sold.
 */
class ProductOrderController extends Controller
{
    public function __construct(private ProductDeliveryService $delivery) {}

    public function index()
    {
        $paid = ProductOrder::paid();

        return view('dashboard.product-orders.index', [
            'salesCount' => (clone $paid)->count(),
            'revenue' => (float) (clone $paid)->sum('amount'),
            'pendingCount' => ProductOrder::where('status', 'pending')->count(),
            'downloads' => (int) ProductOrder::sum('download_count'),
            'productCount' => DigitalProduct::published()->count(),
        ]);
    }

    public function show(ProductOrder $order)
    {
        return view('dashboard.product-orders.show', [
            'order' => $order->load('product'),
        ]);
    }

    public function export()
    {
        return Excel::download(
            new ProductOrdersExport,
            'product-orders-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    public function resend(ProductOrder $order)
    {
        if (! $order->isPaid()) {
            return back()->with('error', 'That order has not been paid for, so there is nothing to send yet.');
        }

        $sent = $this->delivery->deliver($order);

        return back()->with(
            $sent ? 'status' : 'error',
            $sent
                ? 'Download link '.EmailNotificationService::verb().' to '.$order->buyer_email.'.'
                : 'We could not send that email. Check Email Delivery for the reason.',
        );
    }

    public function markPaid(Request $request, ProductOrder $order)
    {
        if ($order->isPaid()) {
            return back()->with('error', 'That order is already paid.');
        }

        $order->update(['status' => 'paid', 'paid_at' => now()]);

        $this->delivery->deliver($order->fresh());

        return back()->with('status', 'Order marked as paid — the download link has gone to '.$order->buyer_email.'.');
    }
}
