<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\TalentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The recruiter's view of the talent pool: candidates whose wanted sectors
 * match a job this company has posted. Names and CVs stay masked until a
 * credit is spent.
 */
class CompanyTalentController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;
        $sectors = $company->jobSectors();

        $query = $company->matchingTalent();

        if ($sector = $request->query('sector')) {
            $query->whereJsonContains('sectors', $sector);
        }

        return view('company.talent', [
            'company' => $company,
            'sectors' => $sectors,
            'activeSector' => $request->query('sector'),
            'profiles' => $query->latest()->paginate(12)->withQueryString(),
            'creditsLeft' => $company->creditsLeft(),
            'unlockedCount' => $company->creditsUsed(),
        ]);
    }

    public function unlock(Request $request, TalentProfile $profile)
    {
        $company = $request->user()->company;

        $result = $company->unlockCv($profile);
        $left = $company->creditsLeft();

        [$key, $message] = match ($result) {
            Company::UNLOCK_OK => ['status', 'CV unlocked — '.$left.' '.Str::plural('credit', $left).' left.'],
            Company::UNLOCK_ALREADY => ['status', 'You already have access to this CV.'],
            Company::UNLOCK_NO_CREDITS => ['error', "You're out of CV credits. Buy more to keep unlocking."],
            default => ['error', 'That candidate does not match any job you have posted.'],
        };

        return redirect()
            ->route('company.talent', $request->only('sector', 'page'))
            ->with($key, $message);
    }

    /**
     * The CV file itself. Two gates: the company must have unlocked this
     * candidate, and the file is on the private disk so there is no other way
     * to reach it.
     */
    public function downloadCv(Request $request, TalentProfile $profile)
    {
        $company = $request->user()->company;

        abort_unless($company->hasUnlocked($profile), 403);
        abort_unless(Storage::disk('local')->exists($profile->cv_path), 404);

        return Storage::disk('local')->download($profile->cv_path, $profile->cv_name);
    }
}
