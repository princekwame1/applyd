<?php

namespace App\Http\Controllers;

use App\Models\JobOpening;
use App\Models\TalentProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The public CV drop. No account, no specific job — the candidate says which
 * sectors they want work in and waits for a matching job to be posted.
 */
class TalentPoolController extends Controller
{
    public function create()
    {
        return view('talent.index', [
            'sectors' => JobOpening::SECTORS,
            'openCount' => JobOpening::open()->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('talent_profiles', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'headline' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'sectors' => ['required', 'array', 'min:1', 'max:5'],
            'sectors.*' => [Rule::in(JobOpening::SECTORS)],
            'summary' => ['nullable', 'string', 'max:1500'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:4096'],
        ], [
            'email.unique' => 'This email already has a CV with us. Email support@applydacademy.com if you need to update it.',
            'sectors.required' => 'Pick at least one sector you want to work in.',
            'sectors.max' => 'Pick up to 5 sectors — the closer the match, the better the results.',
        ]);

        $cv = $request->file('cv');

        TalentProfile::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'headline' => $data['headline'] ?? null,
            'location' => $data['location'] ?? null,
            'sectors' => array_values($data['sectors']),
            'summary' => $data['summary'] ?? null,
            // Private disk. A CV in the pool is only ever served through the
            // company download route, and only after a credit is spent.
            'cv_path' => $cv->store('talent-pool/cv', 'local'),
            'cv_name' => $cv->getClientOriginalName(),
        ]);

        return redirect()
            ->route('talent.create')
            ->with('talent_status', "Your CV is in. We'll show it to employers hiring in the sectors you picked.");
    }
}
