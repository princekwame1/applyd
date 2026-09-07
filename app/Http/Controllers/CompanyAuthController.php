<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Services\JobBoardNotificationService;
use App\Support\GhanaCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CompanyAuthController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route(Auth::user()->hasRole('company') ? 'company.home' : 'dashboard');
        }

        return view('companies.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150', Rule::unique('companies', 'name')],
            'ghana_card' => GhanaCard::rules(),
            'website' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:20000'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $data['description'] = \App\Support\Html::clean($data['description'] ?? null);

        // Stored in one canonical shape, so the duplicate check on the review
        // screen can't be defeated by typing the same card with spaces.
        $data['ghana_card'] = GhanaCard::normalise($data['ghana_card']);

        $company = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['contact_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole('company');

            return Company::create([
                'user_id' => $user->id,
                'name' => $data['company_name'],
                'ghana_card' => $data['ghana_card'],
                'website' => $data['website'] ?? null,
                'location' => $data['location'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => Company::PENDING,
            ]);
        });

        // Outside the transaction: a mail outage must not undo an account the
        // person has already been told to expect. The service swallows its own
        // failures, and an admin can resend from the review screen.
        app(JobBoardNotificationService::class)->companyRegistered($company);

        Auth::login($company->user);

        return redirect()->route('company.home')->with(
            'status',
            'Welcome to Applyd Academy — your account is ready. We are checking your details now; you can start posting jobs straight away.',
        );
    }
}
