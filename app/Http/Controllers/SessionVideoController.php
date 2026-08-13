<?php

namespace App\Http\Controllers;

use App\Exports\SessionVideosExport;
use App\Models\SessionVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SessionVideoController extends Controller
{
    public function export()
    {
        return Excel::download(new SessionVideosExport, 'session-videos-'.now()->format('Y-m-d').'.xlsx');
    }

    public function index()
    {
        return view('dashboard.videos.index');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (SessionVideo::max('sort_order') ?? 0) + 1;

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('videos', 'public');
        }

        SessionVideo::create($data);

        return $this->modalOk($request, 'dashboard.videos', 'Video added.');
    }

    public function edit(Request $request, SessionVideo $video)
    {
        if ($request->ajax()) {
            return view('dashboard.videos.partials.form', ['model' => $video]);
        }

        return view('dashboard.videos.edit', compact('video'));
    }

    public function update(Request $request, SessionVideo $video)
    {
        $data = $this->validated($request, $video);

        if ($request->hasFile('thumbnail')) {
            if ($video->thumbnail) {
                Storage::disk('public')->delete($video->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('videos', 'public');
        }

        $video->update($data);

        return $this->modalOk($request, 'dashboard.videos', 'Video updated.');
    }

    public function destroy(SessionVideo $video)
    {
        if ($video->thumbnail) {
            Storage::disk('public')->delete($video->thumbnail);
        }

        $video->delete();

        return redirect()->route('dashboard.videos')->with('status', 'Video deleted.');
    }

    /**
     * Admins paste a link; what we store is the id. Parsing happens inside the
     * validator so a bad link is reported on the field they actually filled in.
     */
    private function validated(Request $request, ?SessionVideo $video = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'youtube_url' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) use ($video) {
                $id = SessionVideo::parseYoutubeId($value);

                if (! $id) {
                    $fail('Enter a valid YouTube link, e.g. https://www.youtube.com/watch?v=dQw4w9WgXcQ');

                    return;
                }

                $exists = SessionVideo::where('youtube_id', $id)
                    ->when($video, fn ($q) => $q->where('id', '!=', $video->id))
                    ->exists();

                if ($exists) {
                    $fail('That video has already been added.');
                }
            }],
            'session_label' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'recorded_on' => ['nullable', 'date'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data['youtube_id'] = SessionVideo::parseYoutubeId($data['youtube_url']);
        $data['is_published'] = $request->boolean('is_published');
        unset($data['youtube_url']);

        return $data;
    }
}
