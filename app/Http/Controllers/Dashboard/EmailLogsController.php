<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\EmailLogsExport;
use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Services\EmailNotificationService;
use Maatwebsite\Excel\Facades\Excel;

class EmailLogsController extends Controller
{
    public function __construct(protected EmailNotificationService $emails) {}

    public function index()
    {
        return view('dashboard.email-logs');
    }

    public function export()
    {
        return Excel::download(new EmailLogsExport, 'email-logs-'.now()->format('Y-m-d').'.xlsx');
    }

    public function show(EmailLog $emailLog)
    {
        return response()->view('emails.template', [
            'heading' => $emailLog->heading,
            'bodyHtml' => (string) $emailLog->body,
            'ctaLabel' => $emailLog->cta_label,
            'ctaUrl' => $emailLog->cta_url,
        ]);
    }

    public function resend(EmailLog $emailLog)
    {
        $success = $this->emails->resend($emailLog);

        return back()->with(
            $success ? 'success' : 'error',
            $success ? 'Email resent successfully.' : 'Failed to resend the email.'
        );
    }
}
