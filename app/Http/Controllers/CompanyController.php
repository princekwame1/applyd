<?php

namespace App\Http\Controllers;

use App\Models\JobOpening;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;

        return view('company.index', [
            'company' => $company,
            'openings' => $company->openings()->withCount('applications')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->user()->company->openings()->create($this->validated($request));

        return redirect()->route('company.home')->with('status', 'Job opening published.');
    }

    public function edit(Request $request, JobOpening $opening)
    {
        $this->authorizeOpening($request, $opening);

        return view('company.edit-job', compact('opening'));
    }

    public function update(Request $request, JobOpening $opening)
    {
        $this->authorizeOpening($request, $opening);

        $data = $this->validated($request);
        $data['is_open'] = $request->boolean('is_open');

        $opening->update($data);

        return redirect()->route('company.home')->with('status', 'Job opening updated.');
    }

    public function destroy(Request $request, JobOpening $opening)
    {
        $this->authorizeOpening($request, $opening);

        $opening->delete();

        return redirect()->route('company.home')->with('status', 'Job opening deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:20000'],
            'location' => ['nullable', 'string', 'max:150'],
            'type' => ['required', Rule::in(JobOpening::TYPES)],
            'sector' => ['nullable', Rule::in(JobOpening::SECTORS)],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $data['description'] = \App\Support\Html::clean($data['description']);

        return $data;
    }

    private function authorizeOpening(Request $request, JobOpening $opening): void
    {
        abort_unless($opening->company_id === $request->user()->company->id, 403);
    }
}
