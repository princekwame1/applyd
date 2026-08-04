<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Services\SmsNotificationService;

class SmsLogsController extends Controller
{
    public function index()
    {
        return view('dashboard.sms-logs');
    }

    public function retry(SmsLog $smsLog)
    {
        $smsService = new SmsNotificationService();
        $success = $smsService->send($smsLog->phone_number, $smsLog->message, $smsLog->registration_id);

        if ($success) {
            $smsLog->update(['retry_count' => $smsLog->retry_count + 1, 'last_retry_at' => now()]);
            return back()->with('success', 'SMS retry sent successfully');
        }

        return back()->with('error', 'Failed to retry SMS');
    }
}
