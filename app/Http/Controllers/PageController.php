<?php

namespace App\Http\Controllers;

use App\Models\Course;

class PageController extends Controller
{
    public function courses()
    {
        return view('courses', [
            'courses' => Course::ordered()->get(),
        ]);
    }
}
