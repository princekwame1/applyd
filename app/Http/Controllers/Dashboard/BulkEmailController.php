<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\Registration;
use App\Services\EmailNotificationService;
use App\Support\Html;
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
            'opted_in_only' => ['nullable', 'boolean'],
        ], [
            'cta_label.required_with' => 'Add button text, or clear the button link.',
            'cta_url.required_with' => 'Add a button link, or clear the button text.',
        ]);

        // Same scrubbing the template editor applies — the body is admin-authored
        // HTML that ends up in someone else's inbox.
        $validated['body'] = Html::clean($validated['body']);

        $registrations = $this->selectedRegistrations($request);

        if ($request->boolean('opted_in_only')) {
            $registrations = $registrations->where('marketing_opt_in', true);
        }

        if ($registrations->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'No recipients left to send to — the selection expired or the opt-in filter excluded everyone.');
        }

        $sent = 0;

        foreach ($registrations as $registration) {
            $payload = $emails->renderTemplate($validated, $emails->variablesFor($registration));

            if ($emails->send($registration->email, $payload, $registration->id, $registration->full_name, 'bulk_broadcast')) {
                $sent++;
            }
        }

        $failed = $registrations->count() - $sent;

        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('dashboard.registrations')->with(
            $failed === 0 ? 'success' : 'error',
            $failed === 0
                ? $sent.' email'.($sent === 1 ? '' : 's').' sent.'
                : $sent.' sent, '.$failed.' failed — check Email Delivery for the errors.'
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
