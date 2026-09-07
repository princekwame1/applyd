<?php

namespace App\Http\Controllers;

use App\Models\DigitalProduct;
use App\Models\ProductOrder;
use App\Services\ProductDeliveryService;
use App\Support\Paystack;
use App\Support\PaystackFees;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The shop — buying a digital product without an account.
 *
 * Nobody signs up to buy a template, so the order itself is the identity: it
 * is created before the buyer ever reaches Paystack (an abandoned checkout is
 * still traceable) and it carries the `download_token` that is the only route
 * to the file afterwards. That token is emailed and shown on screen; the file
 * has no public URL of its own.
 */
class ShopController extends Controller
{
    public function __construct(private ProductDeliveryService $delivery) {}

    public function index(Request $request)
    {
        $products = DigitalProduct::published()->ordered()->paginate(9)->withQueryString();

        return view('shop', [
            'products' => $products,
            'categories' => DigitalProduct::published()
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
        ]);
    }

    public function show(DigitalProduct $product)
    {
        abort_unless($product->is_published, 404);

        return view('shop-show', [
            'product' => $product,
            'related' => DigitalProduct::published()
                ->where('id', '!=', $product->id)
                ->when($product->category, fn ($q) => $q->where('category', $product->category))
                ->ordered()
                ->take(3)
                ->get(),
        ]);
    }

    public function checkout(Request $request, DigitalProduct $product)
    {
        abort_unless($product->is_published, 404);

        $data = $request->validate([
            'buyer_name' => ['required', 'string', 'max:120'],
            'buyer_email' => ['required', 'email', 'max:190'],
            'buyer_phone' => ['nullable', 'string', 'max:40'],
        ]);

        $price = (float) $product->price;

        $order = ProductOrder::create([
            'digital_product_id' => $product->id,
            // Snapshotted: the catalogue can be renamed or repriced later
            // without changing what this person was sold.
            'product_title' => $product->title,
            'buyer_name' => $data['buyer_name'],
            'buyer_email' => $data['buyer_email'],
            'buyer_phone' => $data['buyer_phone'] ?? null,
            'amount' => $price,
            'fee' => PaystackFees::fee($price),
            'reference' => ProductOrder::newReference(),
            'status' => 'pending',
            'download_token' => ProductOrder::newToken(),
        ]);

        // A free download is a real product, not a missing price — there is
        // nothing to charge, so it is settled here and delivered immediately.
        if ($product->isFree()) {
            $this->settle($order);

            return redirect()->route('shop.download', $order->download_token);
        }

        if (! Paystack::configured()) {
            $order->update(['status' => 'failed']);

            return back()
                ->withInput()
                ->with('error', 'Online payment is not available right now. Please contact us and we will help you buy this.');
        }

        $init = Paystack::initialize([
            'email' => $order->buyer_email,
            // Grossed up so the price still lands in full when the charge is
            // being passed on; `amount` on the row stays the price itself.
            'amount' => PaystackFees::pesewas(PaystackFees::gross($price)),
            'currency' => config('services.paystack.currency', 'GHS'),
            'reference' => $order->reference,
            'callback_url' => route('shop.callback'),
            'metadata' => [
                'order_id' => $order->id,
                'product' => $product->title,
                'buyer_name' => $order->buyer_name,
                'base_amount' => $price,
            ],
        ]);

        if (empty($init['status']) || empty($init['data']['authorization_url'])) {
            $order->update(['status' => 'failed']);

            return back()->withInput()->with('error', 'We could not start the payment. Please try again.');
        }

        return redirect()->away($init['data']['authorization_url']);
    }

    /**
     * Paystack sends the buyer's browser back here. Public, and it has to be:
     * there was never a session to depend on, and the reference itself says
     * which order was paid.
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference', $request->query('trxref'));
        $order = ProductOrder::where('reference', $reference)->first();

        abort_unless($order, 404);

        // Only ever settled once — a callback can be replayed by a refresh,
        // and a second delivery email reads as a second charge.
        if (! $order->isPaid()) {
            $verify = Paystack::configured() ? Paystack::verify($reference) : ['status' => false];

            if (! empty($verify['status']) && ($verify['data']['status'] ?? null) === 'success') {
                $this->settle($order);
            } else {
                $order->update(['status' => 'failed']);
            }
        }

        if (! $order->fresh()->isPaid()) {
            return redirect()
                ->route('shop.show', $order->product)
                ->with('error', 'That payment did not go through, so nothing was charged. You can try again below.');
        }

        return redirect()->route('shop.download', $order->download_token);
    }

    /** The buyer's own page: what they bought, and the way to get it. */
    public function download(string $token)
    {
        $order = ProductOrder::where('download_token', $token)->with('product')->firstOrFail();

        if (! $order->isPaid()) {
            return redirect()
                ->route('shop.show', $order->product)
                ->with('error', 'That order has not been paid for yet.');
        }

        return view('shop-download', ['order' => $order, 'product' => $order->product]);
    }

    /**
     * The file itself. The token is the whole authorisation — there is no
     * account to check — so the only questions are whether it names a settled
     * order and whether the file is still there.
     */
    public function file(string $token)
    {
        $order = ProductOrder::where('download_token', $token)->with('product')->firstOrFail();

        abort_unless($order->isPaid(), 403);

        $product = $order->product;

        abort_unless($product && $product->deliversFile(), 404);
        abort_unless(Storage::disk('local')->exists($product->file_path), 404);

        $order->forceFill([
            'download_count' => $order->download_count + 1,
            'last_downloaded_at' => now(),
        ])->save();

        return Storage::disk('local')->download($product->file_path, $product->file_name ?: $product->slug);
    }

    /** Mark an order paid and send the buyer their link. */
    private function settle(ProductOrder $order): void
    {
        $order->update(['status' => 'paid', 'paid_at' => now()]);

        // Delivery can fail without the sale failing: the link is already on
        // the next page, and an admin can resend it from the orders table.
        $this->delivery->deliver($order->fresh());
    }
}
