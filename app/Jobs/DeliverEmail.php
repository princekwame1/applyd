<?php

namespace App\Jobs;

use App\Models\EmailLog;
use App\Services\EmailNotificationService;
use App\Support\MailThrottle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Push one already-logged email out, at whatever rate the host still allows.
 *
 * Carries the log id rather than the model: the row is the source of truth and
 * may have been resent, edited or deleted between queueing and delivery.
 */
class DeliverEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Unlimited attempts, bounded by retryUntil() instead. Being held back by
     * the throttle is not a failure, but a release still burns an attempt —
     * with a fixed $tries a busy hour would "fail" mail that was never tried.
     */
    public $tries = 0;

    /** Actual errors (a dead SMTP host, a rejected address) still give up. */
    public int $maxExceptions = 3;

    /** @var array<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $emailLogId) {}

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHours(max(1, (int) config('mail.retry_hours', 24)));
    }

    public function handle(EmailNotificationService $emails): void
    {
        $emailLog = EmailLog::find($this->emailLogId);

        // Deleted, or already delivered by a resend that overtook us.
        if (! $emailLog || $emailLog->status === 'sent') {
            return;
        }

        if (! MailThrottle::attempt()) {
            $this->release(MailThrottle::availableIn());

            return;
        }

        // Rethrow so a transient SMTP failure is retried with backoff. The
        // synchronous path still swallows — there it would break a request.
        $emails->deliver($emailLog, rethrow: true);
    }

    /**
     * Out of attempts or out of time. Leave the reason on the row so it shows
     * up on Email Delivery and can be resent by hand.
     */
    public function failed(\Throwable $e): void
    {
        EmailLog::where('id', $this->emailLogId)
            ->where('status', '!=', 'sent')
            ->update([
                'status' => 'failed',
                'response' => Str::limit($e->getMessage(), 1000),
            ]);
    }
}
