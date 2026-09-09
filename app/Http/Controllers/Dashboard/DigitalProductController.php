<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\DigitalProductsExport;
use App\Http\Controllers\Controller;
use App\Models\DigitalProduct;
use App\Support\Html;
use App\Support\ProductFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * The catalogue. Two things here are not ordinary CRUD:
 *
 * A product is delivered either as a file we hold or as a link we send, never
 * both — the form picks one, and `ProductFiles` is the only place either is
 * written, so switching between them always clears the other.
 *
 * A product with a settled order cannot be deleted. Somebody paid for that
 * download and their link still has to resolve; unpublishing is how a product
 * leaves the shop.
 */
class DigitalProductController extends Controller
{
    public function export()
    {
        return Excel::download(new DigitalProductsExport, 'digital-products-'.now()->format('Y-m-d').'.xlsx');
    }

    public function index()
    {
        return view('dashboard.products.index');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (DigitalProduct::max('sort_order') ?? 0) + 1;

        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')->store('products', 'public');
        }

        $product = DigitalProduct::create($data);

        $this->storeDelivery($request, $product);

        return $this->modalOk($request, 'dashboard.products', 'Product added.');
    }

    public function edit(Request $request, DigitalProduct $product)
    {
        if ($request->ajax()) {
            return view('dashboard.products.partials.form', ['model' => $product]);
        }

        return view('dashboard.products.edit', compact('product'));
    }

    public function update(Request $request, DigitalProduct $product)
    {
        $data = $this->validated($request, $product);

        if ($request->hasFile('cover')) {
            ProductFiles::deleteCover($product);
            $data['cover'] = $request->file('cover')->store('products', 'public');
        }

        $product->update($data);

        $this->storeDelivery($request, $product);

        return $this->modalOk($request, 'dashboard.products', 'Product updated.');
    }

    public function destroy(Request $request, DigitalProduct $product)
    {
        // The same rule the table repeats: an order's download has to keep
        // working, so a sold product is unpublished, never deleted.
        if ($reason = static::blockedReason($product)) {
            return $this->modalError($request, 'dashboard.products', $reason);
        }

     
        $product->purgeUnsettledOrders();
        ProductFiles::purge($product);
        $product->delete();

        return redirect()->route('dashboard.products')->with('status', 'Product deleted.');
    }

    /** Why this product may not be deleted, or null when it may. */
    public static function blockedReason(DigitalProduct $product): ?string
    {
        return $product->orders()->paid()->exists()
            ? $product->title.' has been bought — unpublish it instead, so the buyers keep their downloads'
            : null;
    }

    /**
     * Put the delivery in place after the row exists, so a file upload and a
     * link can never both end up set.
     */
    private function storeDelivery(Request $request, DigitalProduct $product): void
    {
        if ($request->input('delivery') === 'link') {
            ProductFiles::useLink($product, (string) $request->input('external_url'));

            return;
        }

        if ($request->hasFile('file')) {
            ProductFiles::storeFile($request->file('file'), $product);
        }
    }

    private function validated(Request $request, ?DigitalProduct $product = null): array
    {
        $delivery = $request->input('delivery') === 'link' ? 'link' : 'file';

        // An existing file counts as the delivery already being there, so an
        // edit that only changes the price doesn't demand the file again.
        $hasFile = $product?->deliversFile() ?? false;

        $data = $request->validate([
            'title' => ['required', 'string', 'max:180', Rule::unique('digital_products', 'title')->ignore($product?->id)],
            'tagline' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:60000'],
            'category' => ['nullable', 'string', 'max:60'],
            'format' => ['nullable', 'string', 'max:40'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'delivery' => ['nullable', Rule::in(['file', 'link'])],
            'file' => [
                $delivery === 'file' && ! $hasFile ? 'required' : 'nullable',
                'file',
                'mimes:'.ProductFiles::MIMES,
                'max:'.ProductFiles::MAX_KB,
            ],
            'external_url' => [$delivery === 'link' ? 'required' : 'nullable', 'url', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
        ], [
            'file.required' => 'Upload the file buyers will download, or switch to a link.',
            'external_url.required' => 'Give the link buyers will be sent, or switch to a file upload.',
        ]);

        $data['description'] = Html::clean($data['description'] ?? '');
        $data['is_published'] = $request->boolean('is_published');

        // Handled by storeDelivery() once the row exists.
        unset($data['file'], $data['external_url'], $data['delivery']);

        return $data;
    }
}
