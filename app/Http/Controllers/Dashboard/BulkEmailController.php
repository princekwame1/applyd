<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\Registration;
use App\Services\EmailNotificationService;
use App\Support\Html;
use App\Support\MailThrottle;
use Illuminate\Http\Request;

class BulkEmailController extends Controller
{
    /**
     * Session key holding the ids picked on the registrations table. Kept in
     * the session rather than the query string so a 100-recipient selection
     * doesn't blow the URL length limit, and so the recipient set can't be
     * swapped by editing the address bar between composing and sending.
     */
    public const SESSION_KEY = 'bulk_email.registration_ids';

    /**
     * Compose screen for the registrants ticked on the registrations table.
     */
    public function create(Request $request)
    {
        $registrations = $this->selectedRegistrations($request);

        if ($registrations->isEmpty()) {
            return redirect()
                ->route('dashboard.registrations')
                ->with('error', 'Pick at least one registrant first, then choose "Send email to selected".');
        }

        return view('dashboard.registrations.bulk-email', [
            'registrations' => $registrations,
            // Split up front so the screen shows who is actually getting this.
            'sendable' => $registrations->where('marketing_opt_in', true)->values(),
            'excluded' => $registrations->where('marketing_opt_in', false)->values(),
            'placeholders' => config('email_templates.placeholders', []),
            'templates' => collect(EmailTemplate::definitions())
                ->map(fn ($definition, $key) => EmailTemplate::resolve($key))
                ->filter()
                ->all(),
        ]);
    }

    /**
     * Render the composed message once per recipient — so {{ first_name }} and
     * friends resolve to that person's own details — and send it.
     */
    public function send(Request $request, EmailNotificationService $emails)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'heading' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'cta_label' => ['nullable', 'string', 'max:100', 'required_with:cta_url'],
            'cta_url' => ['nullable', 'string', 'max:255', 'required_with:cta_label'],
            'service_message' => ['nullable', 'boolean'],
        ], [
            'cta_label.required_with' => 'Add button text, or clear the button link.',
            'cta_url.required_with' => 'Add a button link, or clear the button text.',
        ]);

        // Same scrubbing the template editor applies — the body is admin-authored
        // HTML that ends up in someone else's inbox.
        $validated['body'] = Html::clean($validated['body']);

        $selected = $this->selectedRegistrations($request);

        // Opting out is honoured by default and enforced here, not just in the
        // UI — a stale form or a hand-rolled POST must not be able to reach
        // someone who declined. The only way past it is a deliberate
        // "service message" tick, for non-promotional mail like a schedule
        // change, which marketing consent does not govern.
        $registrations = $request->boolean('service_message')
            ? $selected
            : $selected->where('marketing_opt_in', true);

        $skipped = $selected->count() - $registrations->count();

        if ($registrations->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', $skipped > 0
                    ? 'Nobody in that selection has opted in to marketing, so no email was sent.'
                    : 'No recipients left to send to — the selection expired.');
        }

        $sent = 0;

        foreach ($registrations as $registration) {
            $payload = $emails->renderTemplate($validated, $emails->variablesFor($registration));

            // Onto the bulk queue: a 500-recipient broadcast is paced out over
            // hours by the host's limit and must not sit in front of a
            // confirmation or a student's login.
            if ($emails->send(
                $registration->email,
                $payload,
                $registration->id,
                $registration->full_name,
                'bulk_broadcast',
                EmailNotificationService::BULK,
            )) {
                $sent++;
            }
        }

        $failed = $registrations->count() - $sent;

        $request->session()->forget(self::SESSION_KEY);

        // "Queued", not "sent": the host only accepts so many an hour, so a big
        // broadcast leaves over the next few hours. Saying "sent" here is how
        // an admin ends up sending the whole thing twice.
        $message = $failed === 0
            ? $sent.' email'.($sent === 1 ? '' : 's').' '.EmailNotificationService::verb().'.'
            : $sent.' '.EmailNotificationService::verb().', '.$failed.' failed — check Email Delivery for the errors.';

        if ($failed === 0 && EmailNotificationService::isQueued() && MailThrottle::enabled()) {
            $message .= ' They go out at up to '.MailThrottle::limit().' an hour — watch Email Delivery for progress.';
        }

        // Always say so out loud: a silent drop looks like a delivery failure.
        if ($skipped > 0) {
            $message .= ' '.$skipped.' '.($skipped === 1 ? 'recipient was' : 'recipients were').' skipped — not opted in to marketing.';
        }

        return redirect()->route('dashboard.registrations')->with(
            $failed === 0 ? 'success' : 'error',
            $message
        );
    }

    /**
     * The registrants behind the ids stashed by the table's bulk action.
     */
    protected function selectedRegistrations(Request $request)
    {
        $ids = array_filter(array_map('intval', (array) $request->session()->get(self::SESSION_KEY, [])));

        if (! $ids) {
            return Registration::query()->whereRaw('1 = 0')->get();
        }

        return Registration::whereIn('id', $ids)->orderBy('full_name')->get();
    }
}
