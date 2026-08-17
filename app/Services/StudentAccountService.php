<?php

namespace App\Services;

use App\Models\CourseEnrollment;
use App\Models\User;
use App\Support\Portal;
use App\Support\StudentIds;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Turning a completed course registration into a student: an ID they keep, and
 * an account they can actually sign in with.
 *
 * Two rules shape everything here.
 *
 * The account belongs to the *person*, not the enrolment. Someone registering
 * for a second course is matched on their email, keeps the student ID they
 * already have, and — crucially — keeps the password they already chose. We
 * never reset a working login as a side effect of a new registration.
 *
 * And issuing is idempotent. Registration can complete down two different paths
 * (a free course finishing at the details step, a paid one finishing at the
 * tuition callback), and a callback can be replayed, so this has to be safe to
 * call twice. A plain-text password comes back *only* when one was actually
 * minted — never on a repeat call, because the stored one is a hash and cannot
 * be read back.
 */
class StudentAccountService
{
    public function __construct(
        protected EmailNotificationService $email,
        protected SmsNotificationService $sms,
    ) {}

    /**
     * Give this enrolment a student ID and an account, creating neither twice.
     *
     * @return array{user: User, student_id: string, password: ?string, created: bool}
     */
    public function issueFor(CourseEnrollment $enrollment): array
    {
        return DB::transaction(function () use ($enrollment) {
            $user = $this->resolveUser($enrollment);
            $password = null;
            $created = false;

            if (! $user) {
                $password = $this->temporaryPassword();
                $user = $this->createStudent($enrollment, $password);
                $created = true;
            }

            if (! $user->student_id) {
                $user->student_id = $this->claimStudentId($user);
                $user->save();
            }

            if (! $user->hasRole('student')) {
                $user->assignRole('student');
            }

            $enrollment->forceFill([
                'user_id' => $user->id,
                'student_id' => $user->student_id,
            ])->save();

            return [
                'user' => $user,
                'student_id' => $user->student_id,
                'password' => $password,
                'created' => $created,
            ];
        });
    }

    /**
     * Issue if needed, then send the details by email and SMS. Delivery never
     * breaks the caller: a completed registration must stand even if the mail
     * host or the SMS gateway is down — the admin can resend.
     *
     * @return array{user: User, student_id: string, password: ?string, created: bool}
     */
    public function issueAndNotify(CourseEnrollment $enrollment): array
    {
        $result = $this->issueFor($enrollment);

        $this->notify($enrollment->fresh('course'), $result['password']);

        return $result;
    }

    /**
     * Mint a fresh temporary password and send it out. This is the "resend
     * login details" path: the stored password is a hash, so the old one can
     * never be recovered — the only honest resend is a new one.
     *
     * Skipped for a student who has already chosen their own password; theirs
     * still works and resetting it behind their back would lock them out.
     *
     * @return array{sent: bool, reset: bool, student_id: string}
     */
    public function resendCredentials(CourseEnrollment $enrollment): array
    {
        $result = $this->issueFor($enrollment);
        $user = $result['user'];
        $password = $result['password'];

        if (! $password && $user->must_change_password) {
            $password = $this->temporaryPassword();
            $user->forceFill([
                'password' => $password,
                'must_change_password' => true,
            ])->save();
        }

        $this->notify($enrollment->fresh('course'), $password);

        return [
            'sent' => true,
            'reset' => $password !== null,
            'student_id' => $result['student_id'],
        ];
    }

    /** Email and SMS, each guarded so one failing can't stop the other. */
    public function notify(CourseEnrollment $enrollment, ?string $password): void
    {
        try {
            $this->email->sendTemplate(
                'student_credentials',
                $enrollment->email,
                $this->variablesFor($enrollment, $password),
                null,
                $enrollment->name,
            );
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $this->sms->send($enrollment->phone, $this->smsMessage($enrollment, $password), null, $enrollment->name);
        } catch (\Throwable $e) {
            report($e);
        }

        $enrollment->forceFill(['credentials_sent_at' => now()])->save();
    }

    /**
     * Placeholder values for the student_credentials template. Keys must stay
     * in sync with its `placeholders` list in config/email_templates.php.
     */
    public function variablesFor(CourseEnrollment $enrollment, ?string $password): array
    {
        return [
            'first_name' => $enrollment->first_name,
            'full_name' => (string) $enrollment->name,
            'student_id' => (string) $enrollment->student_id,
            'course_title' => $enrollment->course?->title ?? 'your course',
            'email' => (string) $enrollment->email,
            // Blank when they already have a password of their own — the
            // template's @if-free copy handles that with the line below.
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
     * Kept inside one 160-character SMS segment, and inside the GSM-7 alphabet.
     *
     * Both matter to the bill. A single character outside GSM-7 — an em dash,
     * a curly quote — flips the whole message to UCS-2, where a segment is 70
     * characters, not 160. The first draft of this ran to three segments per
     * student on the strength of one dash. Plain ASCII only here, and no email
     * address: it is long, and the email copy carries it anyway.
     */
    protected function smsMessage(CourseEnrollment $enrollment, ?string $password): string
    {
        $login = Portal::loginUrl();

        if (! $password) {
            return "Hi {$enrollment->first_name}, your Applyd student ID is {$enrollment->student_id}. "
                ."Sign in at {$login} with your usual password.";
        }

        return "Hi {$enrollment->first_name}, your Applyd student ID is {$enrollment->student_id}. "
            ."Sign in at {$login} with password {$password}. Please change it once you are in.";
    }

    /**
     * The account behind this email, if there is one. Matched case-insensitively
     * because people type their address differently each time.
     */
    protected function resolveUser(CourseEnrollment $enrollment): ?User
    {
        if ($enrollment->user_id && $user = User::find($enrollment->user_id)) {
            return $user;
        }

        return User::whereRaw('LOWER(email) = ?', [Str::lower(trim($enrollment->email))])->first();
    }

    protected function createStudent(CourseEnrollment $enrollment, string $password): User
    {
        return User::create([
            'name' => $enrollment->name,
            'email' => Str::lower(trim($enrollment->email)),
            'password' => $password,          // hashed by the model cast
            'must_change_password' => true,
        ]);
    }

    /**
     * Take the next ID, retrying if someone else took it first — two students
     * finishing at the same instant would otherwise race for the same number.
     */
    protected function claimStudentId(User $user): string
    {
        foreach (range(1, 5) as $attempt) {
            $candidate = StudentIds::next();

            if (! User::where('student_id', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Five collisions means something stranger than a race. Fall back to a
        // random 8-digit code rather than leave the student without an ID: it
        // breaks the year-then-sequence reading, but it is still the shape
        // everything else expects, and it is unique.
        Log::warning('Student ID sequence contention', ['user_id' => $user->id]);

        do {
            $candidate = (string) random_int(10000000, 99999999);
        } while (User::where('student_id', $candidate)->exists());

        return $candidate;
    }

    /**
     * Readable over the phone — no look-alike characters, since this gets read
     * off an SMS and typed by hand.
     */
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
