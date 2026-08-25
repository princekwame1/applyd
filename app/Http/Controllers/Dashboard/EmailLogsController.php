<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\EmailLogsExport;
use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Services\EmailNotificationService;
use App\Support\MailThrottle;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class EmailLogsController extends Controller
{
    public function __construct(protected EmailNotificationService $emails) {}

    public function index()
    {
        $waiting = EmailLog::where('status', 'queued')->count();
        $oldest = EmailLog::where('status', 'queued')->min('created_at');
        $lastSent = EmailLog::whereNotNull('sent_at')->max('sent_at');

        return view('dashboard.email-logs', [
            'queue' => [
                'enabled' => EmailNotificationService::isQueued(),
                'waiting' => $waiting,
                'limit' => MailThrottle::limit(),
                'used' => MailThrottle::used(),
                'remaining' => MailThrottle::enabled() ? MailThrottle::remaining() : null,
                'resets_in' => MailThrottle::availableIn(),
                // A backlog on its own is normal — that is the throttle doing
                // its job. What is not normal is mail waiting while there is
                // still allowance left and nothing has gone out for a quarter
                // of an hour: that means no worker is draining the queue, the
                // one failure mode of queueing that is otherwise silent.
                'stalled' => $waiting > 0
                    && (MailThrottle::remaining() > 0)
                    && $oldest !== null
                    && Carbon::parse($oldest)->lt(now()->subMinutes(15))
                    && ($lastSent === null || Carbon::parse($lastSent)->lt(now()->subMinutes(15))),
                'oldest' => $oldest ? Carbon::parse($oldest) : null,
            ],
        ]);
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
            $success
                ? 'Email '.EmailNotificationService::verb().'.'
                : 'Failed to resend the email.'
        );
    }
}
