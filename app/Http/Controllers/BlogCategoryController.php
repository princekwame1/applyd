<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogCategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('blog_categories', 'name')],
        ]);

        BlogCategory::create($data);

        return redirect()->route('dashboard.blog')->with('status', 'Category added.');
    }

    public function destroy(BlogCategory $category)
    {
        $category->delete();

        return redirect()->route('dashboard.blog')->with('status', 'Category deleted.');
    }
}
