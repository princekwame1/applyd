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
}
