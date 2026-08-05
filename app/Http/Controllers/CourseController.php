<?php

namespace App\Http\Controllers;

use App\Exports\CoursesExport;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class CourseController extends Controller
{
    public function export()
    {
        return Excel::download(new CoursesExport, 'courses-'.now()->format('Y-m-d').'.xlsx');
    }

    public function index()
    {
        return view('dashboard.courses.index');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (Course::max('sort_order') ?? 0) + 1;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('courses', 'public');
        }

        Course::create($data);

        return $this->modalOk($request, 'dashboard.courses', 'Course added.');
    }

    public function edit(Request $request, Course $course)
    {
        if ($request->ajax()) {
            return view('dashboard.courses.partials.form', ['model' => $course]);
        }

        return view('dashboard.courses.edit', compact('course'));
    }

    public function update(Request $request, Course $course)
    {
        $data = $this->validated($request, $course);

        if ($request->hasFile('image')) {
            if ($course->image) {
                Storage::disk('public')->delete($course->image);
            }
            $data['image'] = $request->file('image')->store('courses', 'public');
        }

        $course->update($data);

        return $this->modalOk($request, 'dashboard.courses', 'Course updated.');
    }

    public function destroy(Course $course)
    {
        if ($course->image) {
            Storage::disk('public')->delete($course->image);
        }

        $course->delete();

        return redirect()->route('dashboard.courses')->with('status', 'Course deleted.');
    }

    private function validated(Request $request, ?Course $course = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150', Rule::unique('courses', 'title')->ignore($course?->id)],
            'level' => ['nullable', 'string', Rule::in(Course::LEVELS)],
            'duration' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'form_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:20000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data['description'] = \App\Support\Html::clean($data['description'] ?? null);

        return $data;
    }
}
