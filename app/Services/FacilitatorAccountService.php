<?php

namespace App\Services;

use App\Models\User;
use App\Support\Portal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Giving a facilitator a way in.
 *
 * They teach in the sibling portal (applyd-portal) — classes, registers,
 * marking, results all live there — but nobody teaching ever fills in a form
 * on this site, so somebody in the office has to make the account and hand
 * over the login. That is the whole of this class.
 *
 * It follows the student rules deliberately, because they are the same
 * problem: the account belongs to the *person*, so adding a facilitator whose
 * email already exists grants the role to that account rather than making a
 * second one, and **never** resets a password that already works. A resend can
 * only ever be a *new* temporary password, since the stored one is a hash and
 * cannot be read back — so it is minted only for someone still on the one we
 * issued, and left alone for anyone who has chosen their own.
 */
class FacilitatorAccountService
{
    public function __construct(
        protected EmailNotificationService $email,
        protected SmsNotificationService $sms,
    ) {}

    /**
     * Make (or find) the account and give it facilitator access.
     *
     * A plain-text password comes back only when one was actually minted —
     * an existing account keeps whatever it already has.
     *
     * @param  array{name: string, email: string, phone?: ?string}  $data
     * @return array{user: User, password: ?string, created: bool}
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $email = Str::lower(trim($data['email']));
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
            $password = null;
            $created = false;

            if (! $user) {
                $password = $this->temporaryPassword();

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $email,
                    'phone' => $data['phone'] ?? null,
                    'password' => $password,      // hashed by the model cast
                    'must_change_password' => true,
                ]);

                $created = true;
            } elseif (filled($data['phone'] ?? null)) {
                // An existing account keeps its name and password; a number we
                // did not have before is still worth recording.
                $user->forceFill(['phone' => $data['phone']])->save();
            }

            if (! $user->isFacilitator()) {
                // assignRole, not syncRoles: an admin who also teaches keeps
                // being an admin.
                $user->assignRole(User::FACILITATOR_ROLE);
            }

            return ['user' => $user->fresh(), 'password' => $password, 'created' => $created];
        });
    }

    /**
     * Send the sign-in details again, minting a new temporary password for
     * anyone still on ours.
     *
     * @return array{sent: bool, reset: bool}
     */
    public function resendCredentials(User $user): array
    {
        $password = null;

        if ($user->must_change_password) {
            $password = $this->temporaryPassword();

            $user->forceFill([
                'password' => $password,
                'must_change_password' => true,
            ])->save();
        }

        $this->notify($user, $password);

        return ['sent' => true, 'reset' => $password !== null];
    }

    /**
     * Email and SMS, each guarded so one failing cannot stop the other, and
     * neither can break the request that created the account.
     */
    public function notify(User $user, ?string $password): void
    {
        try {
            $this->email->sendTemplate(
                'facilitator_credentials',
                $user->email,
                $this->variablesFor($user, $password),
                null,
                $user->name,
            );
        } catch (\Throwable $e) {
            report($e);
        }

        if (filled($user->phone)) {
            try {
                $this->sms->send($user->phone, $this->smsMessage($user, $password), null, $user->name);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $user->forceFill(['credentials_sent_at' => now()])->save();
    }

    /**
     * Take facilitator access away without touching the account.
     *
     * Deliberately not a delete: their classes, registers and the results they
     * issued all point at this user in the portal, and the account may be an
     * admin's as well. Dropping the role closes the instructor door and leaves
     * every record standing.
     */
    public function revoke(User $user): void
    {
        $user->removeRole(User::FACILITATOR_ROLE);
    }

    /**
     * Placeholder values for the facilitator_credentials template. Keys must
     * stay in sync with its `placeholders` list in config/email_templates.php.
     */
    public function variablesFor(User $user, ?string $password): array
    {
        return [
            'first_name' => $user->first_name,
            'full_name' => (string) $user->name,
            'email' => (string) $user->email,
            'temp_password' => (string) $password,
            'password_line' => $password
                ? 'Temporary password: '.$password
                : 'Sign in with the password you already use for your account.',
            'login_url' => Portal::loginUrl(),
            'site_name' => (string) config('app.name'),
            'site_url' => (string) config('app.url'),
        ];
    }

    /**
     * One 160-character GSM-7 segment, same rule as the student message: a
     * single character outside that alphabet (an em dash, a curly quote) turns
     * the whole message into UCS-2, where a segment is 70 characters and the
     * bill triples. Plain ASCII, and no email address — it is long, and the
     * email carries it anyway.
     */
    protected function smsMessage(User $user, ?string $password): string
    {
        $login = Portal::loginUrl();

        if (! $password) {
            return "Hi {$user->first_name}, you now have facilitator access on Applyd. "
                ."Sign in at {$login} with your usual password.";
        }

        return "Hi {$user->first_name}, your Applyd facilitator login: {$login} "
            ."password {$password}. Please change it after signing in.";
    }

    /** Readable over the phone: no look-alike characters. */
    protected function temporaryPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $password = '';

        for ($i = 0; $i < 10; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }
}
