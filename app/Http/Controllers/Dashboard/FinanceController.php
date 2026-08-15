<?php

namespace App\Http\Controllers\Dashboard;

use App\Exports\FinanceTransactionsExport;
use App\Http\Controllers\Controller;
use App\Models\FinanceDocument;
use App\Models\FinanceTransaction;
use App\Support\Finance;
use App\Support\FinanceDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * The books. Every entry is money in or money out, optionally with the invoice
 * or receipt that backs it attached.
 */
class FinanceController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->period($request);

        $scoped = FinanceTransaction::query()->betweenDates($from, $to);

        return view('dashboard.finance.index', [
            'summary' => Finance::summarise($scoped),
            'incomeByCategory' => Finance::byCategory($scoped, FinanceTransaction::INCOME),
            'expenseByCategory' => Finance::byCategory($scoped, FinanceTransaction::EXPENSE),
            'allTime' => Finance::summarise(FinanceTransaction::query()),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function show(FinanceTransaction $transaction)
    {
        return view('dashboard.finance.show', [
            'transaction' => $transaction->load(['category', 'recorder', 'documents.uploader']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['recorded_by'] = $request->user()->id;

        // The entry and its paperwork land together or not at all.
        $transaction = DB::transaction(function () use ($request, $data) {
            $transaction = FinanceTransaction::create($data);

            $this->attachUploads($request, $transaction);

            return $transaction;
        });

        return $this->modalOk($request, 'dashboard.finance', $transaction->typeLabel().' recorded — '.$transaction->reference.'.');
    }

    public function edit(Request $request, FinanceTransaction $transaction)
    {
        if ($request->ajax()) {
            return view('dashboard.finance.partials.transaction-form', ['model' => $transaction]);
        }

        return redirect()->route('dashboard.finance.transaction', $transaction);
    }

    public function update(Request $request, FinanceTransaction $transaction)
    {
        DB::transaction(function () use ($request, $transaction) {
            $transaction->update($this->validated($request, $transaction));

            $this->attachUploads($request, $transaction);
        });

        return $this->modalOk($request, 'dashboard.finance', 'Entry updated.');
    }

    public function destroy(FinanceTransaction $transaction)
    {
        // The rows cascade; the files on disk don't, so they go first.
        foreach ($transaction->documents as $document) {
            Storage::disk('local')->delete($document->path);
        }

        $transaction->delete();

        return redirect()
            ->route('dashboard.finance')
            ->with('status', 'Entry deleted, along with its documents.');
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->period($request);

        return Excel::download(
            new FinanceTransactionsExport($from, $to),
            'finance-'.($from ?: 'start').'-to-'.($to ?: now()->toDateString()).'.xlsx',
        );
    }

    /**
     * The window the overview is reporting on. Defaults to the year so far,
     * which is what "how are we doing?" usually means.
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function period(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        return [
            $validated['from'] ?? now()->startOfYear()->toDateString(),
            $validated['to'] ?? now()->toDateString(),
        ];
    }

    /** Invoice and receipt come straight off the entry form; both optional. */
    protected function attachUploads(Request $request, FinanceTransaction $transaction): void
    {
        foreach ([FinanceDocument::INVOICE, FinanceDocument::RECEIPT] as $kind) {
            if (! $request->hasFile($kind)) {
                continue;
            }

            FinanceDocuments::store($request->file($kind), $transaction, $kind, $request->user()->id);
        }
    }

    private function validated(Request $request, ?FinanceTransaction $transaction = null): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(FinanceTransaction::TYPES))],
            'finance_category_id' => [
                'nullable',
                // The category has to be on the same side of the books as the
                // entry, or a "Venue hire" income would quietly be possible.
                Rule::exists('finance_categories', 'id')->where('type', $request->input('type')),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'occurred_on' => ['required', 'date', 'before_or_equal:today'],
            'party' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:40'],
            'document_no' => ['nullable', 'string', 'max:60'],
            'note' => ['nullable', 'string', 'max:2000'],
            'invoice' => ['nullable', 'file', 'mimes:'.FinanceDocument::MIMES, 'max:'.FinanceDocument::MAX_KB],
            'receipt' => ['nullable', 'file', 'mimes:'.FinanceDocument::MIMES, 'max:'.FinanceDocument::MAX_KB],
        ], [
            'finance_category_id.exists' => 'Pick a category that belongs to the same side of the books.',
            'occurred_on.before_or_equal' => 'The date can\'t be in the future.',
            'amount.min' => 'Enter an amount greater than zero.',
        ]);

        unset($data['invoice'], $data['receipt']);

        // The reference carries the side of the books in its prefix, so an
        // entry switched from income to expense gets a matching one.
        if ($transaction && $transaction->type !== $data['type']) {
            $data['reference'] = FinanceTransaction::uniqueReference($data['type']);
        }

        return $data;
    }
}
