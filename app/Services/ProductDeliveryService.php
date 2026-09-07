<?php

namespace App\Services;

use App\Models\ProductOrder;
use Illuminate\Support\Facades\Log;

/**
 * The email that carries a buyer's download link.
 *
 * Wrapped in try/catch at the top: a settled payment must stand even if the
 * mailer is down. The link is on screen the moment the order is paid, and an
 * admin can resend from the orders table — losing the email is recoverable,
 * losing the order is not.
 */
class ProductDeliveryService
{
    public function __construct(private EmailNotificationService $emails) {}

    public function deliver(ProductOrder $order): bool
    {
        if (! $order->isPaid()) {
            return false;
        }

        try {
            return $this->emails->sendTemplate(
                'product_delivery',
                $order->buyer_email,
                $this->variablesFor($order),
                null,
                $order->buyer_name,
            );
        } catch (\Throwable $e) {
            Log::error('Product delivery email failed', [
                'order' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @return array<string, string> */
    public function variablesFor(ProductOrder $order): array
    {
        return [
            'first_name' => $order->first_name,
            'full_name' => (string) $order->buyer_name,
            'email' => (string) $order->buyer_email,
            'product_title' => (string) $order->product_title,
            'amount' => $order->amount_label,
            'reference' => (string) $order->reference,
            'download_url' => $order->download_url,
            'purchased_at' => $order->paid_at?->format('F j, Y') ?? '',
            'site_name' => (string) config('app.name'),
            'site_url' => (string) config('app.url'),
        ];
    }
}
