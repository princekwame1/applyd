<?php

namespace App\Services;

use App\Jobs\DeliverEmail;
use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Registration;
use App\Support\Portal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /** Queue name for a broadcast, so it can't hold up transactional mail. */
    public const BULK = 'bulk';

    /**
     * Log one email and hand it to the queue. $payload keys: subject, body,
     * heading, cta_label, cta_url — all already rendered.
     *
     * True means "accepted for delivery", not "in their inbox" — the host's
     * hourly limit decides when it actually leaves. Email Delivery is where
     * the outcome shows up.
     *
     * @param  array{subject:string, body:string, heading?:?string, cta_label?:?string, cta_url?:?string}  $payload
     */
    public function send(
        string $email,
        array $payload,
        ?int $registrationId = null,
        ?string $name = null,
        ?string $templateKey = null,
        ?string $queue = null,
    ): bool {
        // Every email is logged, whether or not it is tied to a registration.
        $emailLog = EmailLog::create([
            'registration_id' => $registrationId,
            'template_key' => $templateKey,
            'name' => $name,
            'email' => $email,
            'subject' => $payload['subject'],
            'body' => $payload['body'],
            'heading' => $payload['heading'] ?? null,
            'cta_label' => $payload['cta_label'] ?? null,
            'cta_url' => $payload['cta_url'] ?? null,
            'status' => 'pending',
        ]);

        return $this->queue($emailLog, $queue);
    }

    /**
     * Hand a logged email to the queue, where the throttle paces it out.
     *
     * With no queue configured (QUEUE_CONNECTION=sync, and every test) this
     * sends inline exactly as it always did — a site without a worker must
     * keep delivering mail rather than silently filling a table.
     */
    public function queue(EmailLog $emailLog, ?string $queue = null): bool
    {
        if (! static::isQueued()) {
            return $this->deliver($emailLog);
        }

        $emailLog->update(['status' => 'queued', 'response' => null]);

        DeliverEmail::dispatch($emailLog->id)->onQueue($this->queueName($queue));

        return true;
    }

    /** Whether mail is being queued at all, or still going out inline. */
    public static function isQueued(): bool
    {
        return config('queue.default') !== 'sync';
    }

    /**
     * The word a flash message should use — mail that has been handed to the
     * queue has not been sent yet, and saying so is the difference between an
     * admin waiting and an admin sending it all over again.
     */
    public static function verb(): string
    {
        return static::isQueued() ? 'queued for delivery' : 'sent';
    }

    protected function queueName(?string $queue): string
    {
        return $queue === self::BULK
            ? (string) config('mail.queues.bulk', 'emails-bulk')
            : (string) config('mail.queues.priority', 'emails');
    }

    /**
     * Push an already-logged email out through the mailer and record the
     * outcome on the log row. Shared by first sends and resends.
     */
    public function deliver(EmailLog $emailLog, bool $rethrow = false): bool
    {
        if (! config('mail.from.address')) {
            Log::warning('Email skipped: MAIL_FROM_ADDRESS not configured');
            $emailLog->update(['status' => 'failed', 'response' => 'MAIL_FROM_ADDRESS is not configured']);

            return false;
        }

        try {
            Log::info('Sending email', [
                'to' => $emailLog->email,
                'subject' => $emailLog->subject,
                'template' => $emailLog->template_key,
                'mailer' => config('mail.default'),
            ]);

            Mail::to($emailLog->email)->send(new TemplatedMail(
                subjectLine: $emailLog->subject,
                bodyHtml: (string) $emailLog->body,
                heading: $emailLog->heading,
                ctaLabel: $emailLog->cta_label,
                ctaUrl: $emailLog->cta_url,
            ));

            $emailLog->update([
                'status' => 'sent',
                'response' => 'Accepted by '.config('mail.default').' mailer',
                'sent_at' => now(),
            ]);

            Log::info('Email sent successfully', ['to' => $emailLog->email]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Email failed', [
                'to' => $emailLog->email,
                'error' => $e->getMessage(),
            ]);

            $emailLog->update([
                'status' => 'failed',
                'response' => $e->getMessage(),
            ]);

            // The queue wants the throw so it can retry with backoff; a web
            // request wants it swallowed so a mail outage can't break the page.
            if ($rethrow) {
                throw $e;
            }

            return false;
        }
    }

    /**
     * Render a registered template for a set of variables and send it.
     */
    public function sendTemplate(
        string $key,
        string $email,
        array $variables,
        ?int $registrationId = null,
        ?string $name = null,
        ?string $queue = null,
    ): bool {
        $template = EmailTemplate::resolve($key);

        if (! $template) {
            Log::warning('Email skipped: unknown template', ['key' => $key]);

            return false;
        }

        if (! $template['enabled']) {
            Log::info('Email skipped: template disabled', ['key' => $key]);

            return false;
        }

        return $this->send(
            $email,
            $this->renderTemplate($template, $variables),
            $registrationId,
            $name,
            $key,
            $queue,
        );
    }

    /**
     * Welcome/confirmation email that goes out right after a bootcamp signup.
     */
    public function sendRegistrationConfirmation(Registration $registration, ?string $queue = null): bool
    {
        return $this->sendTemplate(
            'registration_confirmation',
            $registration->email,
            $this->variablesFor($registration),
            $registration->id,
            $registration->full_name,
            $queue,
        );
    }

    /**
     * Re-send a logged email as-is (same subject and body that was delivered
     * the first time) and bump the retry counter.
     */
    public function resend(EmailLog $emailLog): bool
    {
        $emailLog->update([
            'retry_count' => $emailLog->retry_count + 1,
            'last_retry_at' => now(),
        ]);

        return $this->queue($emailLog);
    }

    /**
     * Substitute {{ token }} placeholders in each field of a resolved template.
     *
     * @return array{subject:string, body:string, heading:?string, cta_label:?string, cta_url:?string}
     */
    public function renderTemplate(array $template, array $variables): array
    {
        return [
            'subject' => $this->render($template['subject'] ?? '', $variables),
            'body' => $this->render($template['body'] ?? '', $variables),
            'heading' => $this->render($template['heading'] ?? '', $variables) ?: null,
            'cta_label' => $this->render($template['cta_label'] ?? '', $variables) ?: null,
            'cta_url' => $this->render($template['cta_url'] ?? '', $variables) ?: null,
        ];
    }

    /**
     * Replace {{ token }} with its value. Unknown tokens are stripped so no
     * raw placeholder ever reaches a recipient.
     */
    public function render(?string $text, array $variables): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        return preg_replace_callback(
            '/\{\{\s*([a-z0-9_]+)\s*\}\}/i',
            fn ($matches) => (string) ($variables[strtolower($matches[1])] ?? ''),
            $text,
        );
    }

    /**
     * Placeholder values for a registration. Keys must stay in sync with
     * config('email_templates.placeholders').
     */
    public function variablesFor(Registration $registration): array
    {
        $fullName = trim((string) $registration->full_name);

        return [
            'first_name' => explode(' ', $fullName)[0] ?: $fullName,
            'full_name' => $fullName,
            'email' => (string) $registration->email,
            'phone' => (string) $registration->full_phone,
            'country' => (string) $registration->country,
            'city' => (string) $registration->city,
            'education' => (string) $registration->education,
            'tools' => implode(', ', $registration->tools ?? []),
            'registered_at' => $registration->created_at?->format('F j, Y') ?? '',
            'site_name' => (string) config('app.name'),
            'site_url' => (string) config('app.url'),
        ];
    }

    /**
     * Sample values used by the template editor preview and test sends. A
     * template with its own placeholder list needs its own sample set, or the
     * preview renders a page of blanks.
     */
    public function sampleVariables(?string $key = null): array
    {
        if ($key === 'student_credentials') {
            return [
                'first_name' => 'Ama',
                'full_name' => 'Ama Mensah',
                'student_id' => '20260007',
                'course_title' => 'Data Analytics',
                'email' => 'ama.mensah@example.com',
                'temp_password' => 'K7RQ2MTXVP',
                'password_line' => 'Temporary password: K7RQ2MTXVP',
                'login_url' => Portal::loginUrl(),
                'site_name' => (string) config('app.name'),
                'site_url' => (string) config('app.url'),
            ];
        }

        return [
            'first_name' => 'Ama',
            'full_name' => 'Ama Mensah',
            'email' => 'ama.mensah@example.com',
            'phone' => '+233 24 123 4567',
            'country' => 'Ghana',
            'city' => 'Accra',
            'education' => 'Bachelor\'s Degree',
            'tools' => 'Notion, Canva, ChatGPT',
            'registered_at' => now()->format('F j, Y'),
            'site_name' => (string) config('app.name'),
            'site_url' => (string) config('app.url'),
        ];
    }
}
