<?php

namespace App\Support;

use App\Models\DigitalProduct;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The one place a product's files are written or removed.
 *
 * The download itself lives on the **private** disk and is only ever served
 * through an order's token — nothing about a product for sale belongs at a
 * public URL. The cover image is the opposite: it exists to be seen, so it
 * goes on the public disk like every other image in the app.
 *
 * Re-uploading replaces: a product has one file, and a second copy nobody
 * points at is disk that fills up quietly.
 */
class ProductFiles
{
    public const MIMES = 'pdf,zip,doc,docx,xls,xlsx,ppt,pptx,csv,txt,epub,psd,ai,png,jpg,jpeg,mp3,mp4';

    /** 100 MB. Bigger than that wants a link to a host built for it. */
    public const MAX_KB = 102400;

    public static function storeFile(UploadedFile $file, DigitalProduct $product): void
    {
        static::deleteFile($product);

        $product->forceFill([
            'file_path' => $file->store('products', 'local'),
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            // A stored file is the delivery, so any old link stops applying.
            'external_url' => null,
        ])->save();
    }

    /** Switch a product over to link delivery, dropping the file it held. */
    public static function useLink(DigitalProduct $product, string $url): void
    {
        static::deleteFile($product);

        $product->forceFill([
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
            'external_url' => $url,
        ])->save();
    }

    public static function deleteFile(DigitalProduct $product): void
    {
        if ($product->file_path && Storage::disk('local')->exists($product->file_path)) {
            Storage::disk('local')->delete($product->file_path);
        }
    }

    public static function deleteCover(DigitalProduct $product): void
    {
        if ($product->cover) {
            Storage::disk('public')->delete($product->cover);
        }
    }

    /** Everything on disk behind a product. The rows cascade; files do not. */
    public static function purge(DigitalProduct $product): void
    {
        static::deleteFile($product);
        static::deleteCover($product);
    }
}
