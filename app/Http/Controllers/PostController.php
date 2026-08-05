<?php

namespace App\Http\Controllers;

use App\Exports\PostsExport;
use App\Models\BlogCategory;
use App\Models\Post;
use App\Support\Html;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PostController extends Controller
{
    public function export()
    {
        return Excel::download(new PostsExport, 'blog-posts-'.now()->format('Y-m-d').'.xlsx');
    }

    public function index()
    {
        return view('dashboard.blog.index', [
            'categories' => BlogCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('posts', 'public');
        }

        Post::create($data);

        return $this->modalOk($request, 'dashboard.blog', 'Post published.');
    }

    public function edit(Request $request, Post $post)
    {
        if ($request->ajax()) {
            return view('dashboard.blog.partials.form', [
                'model' => $post,
                'categories' => BlogCategory::orderBy('name')->get(),
            ]);
        }

        return view('dashboard.blog.edit', [
            'post' => $post,
            'categories' => BlogCategory::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validated($request, $post);

        if ($request->hasFile('cover_image')) {
            if ($post->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('posts', 'public');
        }

        $post->update($data);

        return $this->modalOk($request, 'dashboard.blog', 'Post updated.');
    }

    public function destroy(Post $post)
    {
        if ($post->cover_image) {
            Storage::disk('public')->delete($post->cover_image);
        }

        $post->delete();

        return redirect()->route('dashboard.blog')->with('status', 'Post deleted.');
    }

    private function validated(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180', Rule::unique('posts', 'title')->ignore($post?->id)],
            'blog_category_id' => ['nullable', Rule::exists('blog_categories', 'id')],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:60000'],
            'author' => ['nullable', 'string', 'max:120'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data['body'] = Html::clean($data['body']);
        $data['blog_category_id'] = ($data['blog_category_id'] ?? null) ?: null;
        $data['author'] = ($data['author'] ?? null) ?: $request->user()->name;
        $data['is_published'] = $request->boolean('is_published');
        $data['published_at'] = $data['is_published'] ? ($post?->published_at ?? now()) : null;

        return $data;
    }
}
