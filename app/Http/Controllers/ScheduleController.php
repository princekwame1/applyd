<?php

namespace App\Http\Controllers;

use App\Exports\SchedulesExport;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ScheduleController extends Controller
{
    public function export()
    {
        return Excel::download(new SchedulesExport, 'schedules-'.now()->format('Y-m-d').'.xlsx');
    }

    public function index()
    {
        return view('dashboard.schedules.index');
    }

    public function store(Request $request)
    {
        Schedule::create($this->validated($request));

        return redirect()->route('dashboard.schedules')->with('status', 'Schedule entry added.');
    }

    public function edit(Schedule $schedule)
    {
        return view('dashboard.schedules.edit', compact('schedule'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $schedule->update($this->validated($request));

        return redirect()->route('dashboard.schedules')->with('status', 'Schedule entry updated.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('dashboard.schedules')->with('status', 'Schedule entry deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'week_label' => ['required', 'string', 'max:100'],
            'focus' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
