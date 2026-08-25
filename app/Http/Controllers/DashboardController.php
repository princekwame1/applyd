<?php

namespace App\Http\Controllers;

use App\Exports\RegistrationsExport;
use App\Models\Course;
use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Tool;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function home()
    {
        return view('dashboard.home', [
            'counts' => [
                'registrations' => Registration::count(),
                'today' => Registration::whereDate('created_at', now()->toDateString())->count(),
                'week' => Registration::where('created_at', '>=', now()->startOfWeek())->count(),
                'tools' => Tool::count(),
                'courses' => Course::count(),
                'schedules' => Schedule::count(),
                'users' => User::count(),
            ],
            'recent' => Registration::latest()->take(6)->get(),
        ]);
    }

    public function index(Request $request)
    {
        $countryCounts = $this->registrationsByCountry();

        $stats = [
            'total' => Registration::count(),
            // Counted off the breakdown so the number on the card and the list
            // behind it can never disagree.
            'countries' => $countryCounts->count(),
            'opted_in' => Registration::where('marketing_opt_in', true)->count(),
            'today' => Registration::whereDate('created_at', now()->toDateString())->count(),
        ];

        $topTools = Registration::pluck('tools')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(5);

        return view('dashboard.index', compact('stats', 'topTools', 'countryCounts'));
    }

    /**
     * Behind the "Countries Reached" card: which countries, and how many
     * registrations from each. Grouped in SQL — `country` is a real column,
     * unlike `tools`. Keyed by country, biggest first.
     */
    private function registrationsByCountry(): Collection
    {
        return Registration::query()
            ->selectRaw('country, count(*) as registrations')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('registrations')
            ->orderBy('country')
            ->pluck('registrations', 'country');
    }

    public function show(Registration $registration)
    {
        return view('dashboard.show', [
            'registration' => $registration,
            'emailLogs' => $registration->emailLogs()->latest()->get(),
        ]);
    }

    /**
     * Re-send the registration confirmation email, re-rendered from the
     * current template so the registrant gets the latest wording.
     */
    public function resendEmail(Registration $registration, EmailNotificationService $emails)
    {
        $success = $emails->sendRegistrationConfirmation($registration);

        return back()->with(
            $success ? 'success' : 'error',
            $success
                ? 'Confirmation email '.EmailNotificationService::verb().' — '.$registration->email.'.'
                : 'Could not send the email — check Email Delivery for the error.'
        );
    }

    public function export()
    {
        return Excel::download(new RegistrationsExport, 'registrations-'.now()->format('Y-m-d').'.xlsx');
    }
}
