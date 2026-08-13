<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\PlanPurchasesExport;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PlanPurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class PlanPurchaseController extends Controller
{
    public function index()
    {
        $paid = PlanPurchase::paid();

        return view('dashboard.plan-purchases.index', [
            'revenue' => (clone $paid)->sum('amount'),
            'creditsSold' => (clone $paid)->sum('credits'),
            'paidCount' => (clone $paid)->count(),
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    /**
     * Credits granted by hand — paid by bank transfer, agreed off-platform, or
     * handed out as a trial. Recorded as a settled purchase so the balance is
     * always the sum of one table, with no second way to earn credits.
     */
    public function grant(Request $request)
    {
        $data = $request->validate([
            'company_id' => ['required', Rule::exists('companies', 'id')],
            'credits' => ['required', 'integer', 'min:1', 'max:100000'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'note' => ['nullable', 'string', 'max:80'],
        ]);

        $company = Company::findOrFail($data['company_id']);

        $company->purchases()->create([
            'plan_name' => $data['note'] ?: 'Manual grant',
            'credits' => $data['credits'],
            'amount' => $data['amount'] ?? 0,
            'reference' => 'MANUAL-'.$company->id.'-'.strtoupper(Str::random(8)),
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return redirect()
            ->route('dashboard.plan-purchases')
            ->with('status', $data['credits'].' credits added to '.$company->name.'.');
    }

    public function export()
    {
        return Excel::download(new PlanPurchasesExport, 'plan-purchases-'.now()->format('Y-m-d').'.xlsx');
    }
}
