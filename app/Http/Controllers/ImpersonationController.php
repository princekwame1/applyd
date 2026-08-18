<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Impersonation;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function start(Request $request, User $user)
    {
        if ($reason = Impersonation::blockedReason($request->user(), $user)) {
            return back()->with('error', $reason);
        }

        $home = Impersonation::start($request->user(), $user);

        return redirect()->to($home)->with('status', 'You are now signed in as '.$user->name.'.');
    }

    /**
     * Deliberately open to any signed-in account: the person pressing it *is*
     * the impersonated user, who may hold no permissions at all. The session
     * key is the authority, and with no key this is a harmless no-op.
     */
    public function stop(Request $request)
    {
        if (! Impersonation::active()) {
            return redirect()->to(Impersonation::homeFor($request->user()));
        }

        $home = Impersonation::stop();

        return redirect()->to($home)->with('status', 'Back to your own account.');
    }
}
