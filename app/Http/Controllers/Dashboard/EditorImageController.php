<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EditorImageController extends Controller
{
    /**
     * Receive an image dropped into a Quill editor and hand back a public URL.
     *
     * Quill's stock image button inlines a base64 data URI, which is useless in
     * email — Gmail and Outlook strip data: images, and the payload bloats the
     * stored body. Uploading and embedding a real URL keeps the body small and
     * the image visible in an inbox.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ], [
            'image.max' => 'Images must be 2MB or smaller — large images get clipped by email clients.',
        ]);

        $path = $request->file('image')->store('editor', 'public');
        $url = Storage::disk('public')->url($path);

        // Force absolute: a root-relative path means nothing once the body is
        // sitting in someone's inbox, and a misconfigured disk `url` would
        // otherwise ship silently broken images.
        return response()->json([
            'url' => str_starts_with($url, 'http') ? $url : url($url),
        ]);
    }
}
