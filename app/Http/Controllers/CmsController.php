<?php

namespace App\Http\Controllers;

use App\Models\PageContent;
use App\Support\Cms;
use App\Support\Html;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CmsController extends Controller
{
    public function index()
    {
        return view('dashboard.cms.index', [
            'pages' => config('cms.pages', []),
        ]);
    }

    public function edit(string $page)
    {
        $config = config("cms.pages.$page");
        abort_unless($config, 404);

        return view('dashboard.cms.edit', [
            'page' => $page,
            'config' => $config,
        ]);
    }

    public function update(Request $request, string $page)
    {
        $config = config("cms.pages.$page");
        abort_unless($config, 404);

        foreach ($config['sections'] as $section) {
            foreach ($section['fields'] as $key => $field) {
                $type = $field['type'] ?? 'text';

                if ($type === 'image') {
                    if ($request->hasFile("fields.$key")) {
                        $request->validate(["fields.$key" => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);
                        $path = $request->file("fields.$key")->store('cms', 'public');
                        $this->put($page, $key, $path);
                    }

                    continue;
                }

                $value = $request->input("fields.$key");

                if ($type === 'richtext' || $type === 'textarea') {
                    $value = Html::clean($value);
                }

                $this->put($page, $key, $value);
            }
        }

        Cms::flush();

        return redirect()->route('dashboard.cms.edit', $page)->with('status', 'Content updated.');
    }

    protected function put(string $page, string $key, ?string $value): void
    {
        PageContent::updateOrCreate(
            ['page' => $page, 'key' => $key],
            ['value' => $value]
        );
    }
}
