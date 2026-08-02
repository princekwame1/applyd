<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Tool;

class PageController extends Controller
{
    public function courses()
    {
        $tools = Tool::ordered()->get();

        return view('courses', [
            'tools' => $tools,
            'toolCategories' => $tools->groupBy('category')->map(fn ($group) => $group->pluck('name')->all()),
            'schedules' => Schedule::ordered()->get(),
        ]);
    }
}
