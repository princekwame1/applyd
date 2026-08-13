<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\Course;
use App\Models\Post;
use App\Models\SessionVideo;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function courses()
    {
        return view('courses', [
            'courses' => Course::ordered()->get(),
        ]);
    }

    public function showCourse(Course $course)
    {
        return view('course-show', [
            'course' => $course,
            'related' => Course::where('id', '!=', $course->id)
                ->when($course->level, fn ($q) => $q->where('level', $course->level))
                ->ordered()
                ->take(3)
                ->get(),
        ]);
    }

    public function videos()
    {
        return view('videos', [
            'videos' => SessionVideo::published()->ordered()->paginate(12),
        ]);
    }

    public function blog(Request $request)
    {
        $activeCategory = null;

        $posts = Post::query()
            ->published()
            ->with('category')
            ->when($request->query('category'), function ($q) use ($request, &$activeCategory) {
                $activeCategory = BlogCategory::where('slug', $request->query('category'))->first();
                if ($activeCategory) {
                    $q->where('blog_category_id', $activeCategory->id);
                }
            })
            ->newestFirst()
            ->paginate(9)
            ->withQueryString();

        return view('blog', [
            'posts' => $posts,
            'categories' => BlogCategory::orderBy('name')->get(),
            'activeCategory' => $activeCategory,
        ]);
    }

    public function showPost(Post $post)
    {
        abort_unless($post->is_published && (! $post->published_at || $post->published_at <= now()), 404);

        return view('blog-show', [
            'post' => $post->load('category'),
            'related' => Post::published()
                ->where('id', '!=', $post->id)
                ->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))
                ->with('category')
                ->newestFirst()
                ->take(3)
                ->get(),
        ]);
    }
}
