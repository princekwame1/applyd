<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
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
            'website' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:20000'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $data['description'] = \App\Support\Html::clean($data['description'] ?? null);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['contact_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $user->assignRole('company');

            Company::create([
                'user_id' => $user->id,
                'name' => $data['company_name'],
                'website' => $data['website'] ?? null,
                'location' => $data['location'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('company.home')->with('status', 'Welcome to Applyd Academy — your company account is ready.');
    }
}
