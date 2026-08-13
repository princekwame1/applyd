<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\RecruiterPlansExport;
use App\Http\Controllers\Controller;
use App\Models\RecruiterPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class RecruiterPlanController extends Controller
{
    public function index()
    {
        return view('dashboard.recruiter-plans.index');
    }

    public function export()
    {
        return Excel::download(new RecruiterPlansExport, 'recruiter-plans-'.now()->format('Y-m-d').'.xlsx');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] = (RecruiterPlan::max('sort_order') ?? 0) + 1;

        RecruiterPlan::create($data);

        return $this->modalOk($request, 'dashboard.recruiter-plans', 'Plan added.');
    }

    public function edit(Request $request, RecruiterPlan $plan)
    {
        if ($request->ajax()) {
            return view('dashboard.recruiter-plans.partials.form', ['model' => $plan]);
        }

        return redirect()->route('dashboard.recruiter-plans');
    }

    public function update(Request $request, RecruiterPlan $plan)
    {
        $plan->update($this->validated($request, $plan));

        return $this->modalOk($request, 'dashboard.recruiter-plans', 'Plan updated.');
    }

    /**
     * Purchases snapshot the plan name and credits, so retiring a plan never
     * touches what anyone already bought.
     */
    public function destroy(RecruiterPlan $plan)
    {
        $plan->delete();

        return redirect()
            ->route('dashboard.recruiter-plans')
            ->with('status', 'Plan deleted. Credits already bought on it are unaffected.');
    }

    private function validated(Request $request, ?RecruiterPlan $plan = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => [
                'nullable', 'string', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('recruiter_plans', 'slug')->ignore($plan?->id),
            ],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'cv_credits' => ['required', 'integer', 'min:1', 'max:100000'],
            'blurb' => ['nullable', 'string', 'max:255'],
            'features' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ], [
            'slug.regex' => 'The slug must be lower-case letters, numbers and dashes.',
        ]);

        if (empty($data['slug'])) {
            unset($data['slug']);
        }

        // One feature per line, the same shape as the survey option editor.
        $data['features'] = collect(preg_split('/\r\n|\r|\n/', (string) ($data['features'] ?? '')))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }
}
