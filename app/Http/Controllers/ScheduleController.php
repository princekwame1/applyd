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
        $data = $this->validated($request);
        $data['sort_order'] = (Schedule::max('sort_order') ?? 0) + 1;

        Schedule::create($data);

        return $this->modalOk($request, 'dashboard.schedules', 'Schedule entry added.');
    }

    public function edit(Request $request, Schedule $schedule)
    {
        if ($request->ajax()) {
            return view('dashboard.schedules.partials.form', ['model' => $schedule]);
        }

        return view('dashboard.schedules.edit', compact('schedule'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $schedule->update($this->validated($request));

        return $this->modalOk($request, 'dashboard.schedules', 'Schedule entry updated.');
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
        ]);
    }
}
