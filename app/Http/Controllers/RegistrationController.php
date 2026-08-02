<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Tool;
use App\Services\SmsNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class RegistrationController extends Controller
{
    public function landing()
    {
        $tools = Tool::ordered()->get();

        return view('landing', [
            'tools' => $tools,
            'toolCategories' => $tools->groupBy('category')->map(fn ($group) => $group->pluck('name')->all()),
            'genders' => config('bootcamp.genders'),
            'ageRanges' => config('bootcamp.age_ranges'),
            'educationLevels' => config('bootcamp.education_levels'),
            'schedules' => Schedule::ordered()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $allTools = Tool::pluck('name')->all();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(config('bootcamp.genders'))],
            'age_range' => ['required', Rule::in(config('bootcamp.age_ranges'))],
            'country' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:25', 'regex:/^\+[0-9 \-]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:registrations,email'],
            'education' => ['required', Rule::in(config('bootcamp.education_levels'))],
            'tools' => ['required', 'array', 'min:1'],
            'tools.*' => [Rule::in($allTools)],
            'marketing_opt_in' => ['nullable', 'boolean'],
        ], [
            'tools.required' => 'Please select at least one tool you want to learn.',
            'tools.min' => 'Please select at least one tool you want to learn.',
            'phone.regex' => 'Please include your country code, e.g. +233 24 123 4567.',
            'email.unique' => 'You have already registered with this email address. Please use a different email or contact us if you need help.',
        ]);

        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            $parsedPhone = $phoneUtil->parse($validated['phone'], null);
        } catch (NumberParseException) {
            $parsedPhone = null;
        }

        if ($parsedPhone === null || ! $phoneUtil->isValidNumber($parsedPhone)) {
            throw ValidationException::withMessages([
                'phone' => 'Enter a valid phone number with country code, e.g. +233 24 123 4567.',
            ]);
        }

        $validated['phone_country_code'] = '+'.$parsedPhone->getCountryCode();
        $validated['phone'] = (string) $parsedPhone->getNationalNumber();

        // Check if phone + country code already registered
        $existingPhone = Registration::where('phone_country_code', $validated['phone_country_code'])
            ->where('phone', $validated['phone'])
            ->exists();

        if ($existingPhone) {
            throw ValidationException::withMessages([
                'phone' => 'You have already registered with this phone number. Please use a different phone or contact us if you need help.',
            ]);
        }

        $validated['marketing_opt_in'] = $request->boolean('marketing_opt_in');

        $registration = Registration::create($validated);

        $firstName = explode(' ', trim($registration->full_name))[0];
        $fullPhone = $validated['phone_country_code'].$validated['phone'];

        app(SmsNotificationService::class)->sendRegistrationConfirmation($fullPhone, $firstName);

        return redirect()
            ->route('register.thanks')
            ->with('registered_name', $firstName);
    }

    public function thanks()
    {
        if (! session()->has('registered_name')) {
            return redirect()->route('landing');
        }

        return view('thanks', [
            'firstName' => session('registered_name'),
            'socialLinks' => config('bootcamp.social_links'),
        ]);
    }
}
