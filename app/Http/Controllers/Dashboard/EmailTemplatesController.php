<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\EmailNotificationService;
use App\Support\Html;
use Illuminate\Http\Request;

class EmailTemplatesController extends Controller
{
    public function __construct(protected EmailNotificationService $emails) {}

    public function index()
    {
        $templates = collect(EmailTemplate::definitions())
            ->keys()
            ->mapWithKeys(fn ($key) => [$key => EmailTemplate::resolve($key)]);

        return view('dashboard.email-templates.index', [
            'templates' => $templates,
            'mailer' => config('mail.default'),
            'fromAddress' => config('mail.from.address'),
            // Why mail isn't going anywhere, or null if it is. A half-filled
            // Mailgun block looks exactly like a working one from the outside
            // — the mailer reads "mailgun" and nothing is sent — so name the
            // missing piece rather than leaving an admin to guess.
            'notLive' => $this->notLiveReason(),
        ]);
    }

    /**
     * The one thing standing between this install and real delivery, in the
     * order an admin would fix them.
     */
    protected function notLiveReason(): ?string
    {
        $mailer = config('mail.default');

        if ($mailer === 'log' || $mailer === 'array') {
            return 'Mail is only being written to the log. Set MAIL_MAILER=mailgun in .env.';
        }

        if (! config('mail.from.address')) {
            return 'No sender address. Set MAIL_FROM_ADDRESS in .env.';
        }

        if ($mailer === 'mailgun' && ! config('services.mailgun.secret')) {
            return 'Mailgun has no API key. Set MAILGUN_SECRET in .env (the key\'s secret, shown once when it was created).';
        }

        if ($mailer === 'mailgun' && ! config('services.mailgun.domain')) {
            return 'Mailgun has no sending domain. Set MAILGUN_DOMAIN in .env to the verified domain.';
        }

        return null;
    }

    public function edit(string $key)
    {
        $template = EmailTemplate::resolve($key);
        abort_unless($template, 404);

        return view('dashboard.email-templates.edit', [
            'key' => $key,
            'template' => $template,
            // A template may declare its own tokens; the shared list is only
            // the fallback. Offering a bootcamp token on a student email would
            // just be an invitation to insert something that renders blank.
            'placeholders' => config("email_templates.templates.$key.placeholders")
                ?? config('email_templates.placeholders', []),
        ]);
    }

    public function update(Request $request, string $key)
    {
        abort_unless(EmailTemplate::definition($key), 404);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'heading' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'cta_label' => ['nullable', 'string', 'max:120'],
            'cta_url' => ['nullable', 'string', 'max:255'],
        ]);

        EmailTemplate::updateOrCreate(['key' => $key], [
            'subject' => $validated['subject'],
            'heading' => $validated['heading'] ?: null,
            'body' => Html::clean($validated['body']),
            'cta_label' => $validated['cta_label'] ?: null,
            'cta_url' => $validated['cta_url'] ?: null,
            'enabled' => $request->boolean('enabled'),
        ]);

        return redirect()
            ->route('dashboard.email-templates.edit', $key)
            ->with('status', 'Email template saved.');
    }

    /**
     * Drop the admin override so the template falls back to the copy shipped
     * in config/email_templates.php.
     */
    public function reset(string $key)
    {
        abort_unless(EmailTemplate::definition($key), 404);

        EmailTemplate::where('key', $key)->delete();

        return redirect()
            ->route('dashboard.email-templates.edit', $key)
            ->with('status', 'Template reset to the default copy.');
    }

    /**
     * Rendered preview (sample data) shown in the editor's iframe.
     */
    public function preview(string $key)
    {
        $template = EmailTemplate::resolve($key);
        abort_unless($template, 404);

        $rendered = $this->emails->renderTemplate($template, $this->emails->sampleVariables($key));

        return response()->view('emails.template', [
            'heading' => $rendered['heading'],
            'bodyHtml' => $rendered['body'],
            'ctaLabel' => $rendered['cta_label'],
            'ctaUrl' => $rendered['cta_url'],
        ]);
    }

    /**
     * Fire a one-off copy at an address so the admin can eyeball it in a real
     * inbox (and confirm the Mailgun credentials work).
     */
    public function test(Request $request, string $key)
    {
        $template = EmailTemplate::resolve($key);
        abort_unless($template, 404);

        $validated = $request->validate([
            'test_email' => ['required', 'email', 'max:255'],
        ]);

        $rendered = $this->emails->renderTemplate($template, $this->emails->sampleVariables($key));
        $rendered['subject'] = '[TEST] '.$rendered['subject'];

        $success = $this->emails->send(
            $validated['test_email'],
            $rendered,
            null,
            'Test send',
            $key,
        );

        return back()->with(
            $success ? 'status' : 'error',
            $success
                ? 'Test email '.EmailNotificationService::verb().' — '.$validated['test_email'].'.'
                : 'Test email failed — check Email Delivery for the error.'
        );
    }
}
