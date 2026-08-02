<?php

namespace App\Http\Controllers;

use App\Exports\RegistrationsExport;
use App\Models\Course;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function home()
    {
        return view('dashboard.home', [
            'counts' => [
                'registrations' => Registration::count(),
                'today' => Registration::whereDate('created_at', now()->toDateString())->count(),
                'tools' => Tool::count(),
                'courses' => Course::count(),
                'schedules' => Schedule::count(),
                'users' => User::count(),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $stats = [
            'total' => Registration::count(),
            'countries' => Registration::distinct('country')->count('country'),
            'opted_in' => Registration::where('marketing_opt_in', true)->count(),
            'today' => Registration::whereDate('created_at', now()->toDateString())->count(),
        ];

        $topTools = Registration::pluck('tools')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(5);

        return view('dashboard.index', compact('stats', 'topTools'));
    }

    public function show(Registration $registration)
    {
        return view('dashboard.show', compact('registration'));
    }

    public function export()
    {
        return Excel::download(new RegistrationsExport, 'registrations-'.now()->format('Y-m-d').'.xlsx');
    }
}
