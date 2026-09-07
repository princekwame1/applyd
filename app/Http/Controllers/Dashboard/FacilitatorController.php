<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\FacilitatorsExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\FacilitatorAccountService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Facilitator accounts and the way into the portal.
 *
 * The teaching itself is the portal's (classes, registers, materials, marking,
 * results) and stays there — this screen exists because none of it is reachable
 * until somebody has an account and knows the password, and there was nowhere
 * on this site that did that.
 */
class FacilitatorController extends Controller
{
    public function __construct(private FacilitatorAccountService $accounts) {}

    public function index()
    {
        $facilitators = User::facilitators();

        return view('dashboard.facilitators.index', [
            'total' => (clone $facilitators)->count(),
            'active' => (clone $facilitators)->where('must_change_password', false)->whereNotNull('credentials_sent_at')->count(),
            'awaiting' => (clone $facilitators)->whereNull('credentials_sent_at')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $result = $this->accounts->create($data);
        $user = $result['user'];
        $wanted = $request->boolean('send_credentials', true);
        $sent = $wanted ? $this->accounts->notify($user, $result['password']) : null;

        return $this->modalOk($request, 'dashboard.facilitators', $this->createdMessage($result, $sent));
    }

    public function edit(Request $request, User $facilitator)
    {
        abort_unless($facilitator->isFacilitator(), 404);

        if ($request->ajax()) {
            return view('dashboard.facilitators.partials.form', ['model' => $facilitator]);
        }

        return view('dashboard.facilitators.edit', ['facilitator' => $facilitator]);
    }

    public function update(Request $request, User $facilitator)
    {
        abort_unless($facilitator->isFacilitator(), 404);

        $data = $this->validated($request, $facilitator);

        // Name, email and number only. A password is never set from here —
        // "resend login details" is the one way one is issued, so there is
        // never a password typed into a form and then read off a screen.
        $facilitator->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        return $this->modalOk($request, 'dashboard.facilitators', 'Facilitator updated.');
    }

    /**
     * Send the sign-in details again. A new temporary password is minted only
     * for someone still on the one we issued; anyone who has chosen their own
     * keeps it and just gets the reminder — and the message says which
     * happened, because those are very different things to the person reading.
     */
    public function resendCredentials(User $facilitator)
    {
        abort_unless($facilitator->isFacilitator(), 404);

        $result = $this->accounts->resendCredentials($facilitator);

        if (! $result['sent']) {
            return back()->with('error', $this->failureNote($facilitator, $result['reset']));
        }

        $verb = EmailNotificationService::verb();

        return back()->with('status', $result['reset']
            ? 'New temporary password '.$verb.' to '.$facilitator->email.'. Their previous one no longer works.'
            : 'Sign-in reminder '.$verb.' to '.$facilitator->email.'. They keep the password they chose.');
    }

    /**
     * Close the instructor door without touching the account. Their classes,
     * registers and issued results all point at this user in the portal, so
     * deleting would take real work down with it — and the account may be an
     * admin's as well. Deleting an account outright is still /dashboard/users.
     */
    public function revoke(Request $request, User $facilitator)
    {
        abort_unless($facilitator->isFacilitator(), 404);

        if ($facilitator->id === $request->user()->id) {
            return back()->with('error', "You can't take facilitator access off your own account.");
        }

        $this->accounts->revoke($facilitator);

        return redirect()->route('dashboard.facilitators')
            ->with('status', $facilitator->name.' can no longer reach the instructor side. Their classes and marking are untouched.');
    }

    public function export()
    {
        return Excel::download(new FacilitatorsExport, 'facilitators-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * A send that did not leave must never read as one that did — that is the
     * difference between an admin who fixes it and an admin who waits.
     */
    private function failureNote(User $facilitator, bool $reset): string
    {
        return 'We could not email '.$facilitator->email.'. See Email Delivery for the reason.'
            .($reset ? ' Their password was still reset, so send it again once that is fixed.' : '');
    }

    /** @param  bool|null  $notified  null when nothing was meant to go out. */
    private function createdMessage(array $result, ?bool $notified): string
    {
        $name = $result['user']->name;

        if ($notified === false) {
            return $name.' was added, but we could not email '.$result['user']->email
                .'. See Email Delivery for the reason, then use the paper-plane button to try again.';
        }

        if (! $notified) {
            return $result['created']
                ? $name.' added. Nothing has been sent — use the paper-plane button when you are ready.'
                : $name.' already had an account and now has facilitator access. Nothing was sent.';
        }

        $verb = EmailNotificationService::verb();

        return $result['created']
            ? $name.' added and their login '.$verb.'.'
            : $name.' already had an account, so they keep their existing password. A sign-in reminder was '.$verb.'.';
    }

    /**
     * Note what is *not* here on create: a uniqueness rule on the email.
     *
     * The account belongs to the person, so somebody who already has one — a
     * past student now teaching, an admin taking a class — must be granted
     * facilitator access rather than refused as a duplicate. The service
     * matches on the address and attaches the role; editing still enforces
     * uniqueness, since that is a genuine clash.
     */
    private function validated(Request $request, ?User $facilitator = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => array_filter([
                'required', 'email', 'max:255',
                $facilitator ? Rule::unique('users', 'email')->ignore($facilitator->id) : null,
            ]),
            'phone' => ['nullable', 'string', 'max:40'],
        ]);
    }
}
