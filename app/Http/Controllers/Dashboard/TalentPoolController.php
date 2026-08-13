<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\TalentProfilesExport;
use App\Http\Controllers\Controller;
use App\Models\CvUnlock;
use App\Models\TalentProfile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Admin oversight of the CV pool. Admins can read every profile — the credit
 * gate is a commercial rule for recruiters, not a privacy wall against staff.
 */
class TalentPoolController extends Controller
{
    public function index()
    {
        return view('dashboard.talent-pool.index', [
            'total' => TalentProfile::count(),
            'available' => TalentProfile::available()->count(),
            'unlocks' => CvUnlock::count(),
        ]);
    }

    public function export()
    {
        return Excel::download(new TalentProfilesExport, 'talent-pool-'.now()->format('Y-m-d').'.xlsx');
    }

    public function show(TalentProfile $profile)
    {
        return view('dashboard.talent-pool.partials.profile', [
            'profile' => $profile->load('unlocks.company'),
        ]);
    }

    public function downloadCv(TalentProfile $profile)
    {
        abort_unless(Storage::disk('local')->exists($profile->cv_path), 404);

        return Storage::disk('local')->download($profile->cv_path, $profile->cv_name);
    }

    public function destroy(TalentProfile $profile)
    {
        if ($profile->cv_path) {
            Storage::disk('local')->delete($profile->cv_path);
        }

        $profile->delete();

        return redirect()
            ->route('dashboard.talent-pool')
            ->with('status', 'Profile deleted.');
    }
}
