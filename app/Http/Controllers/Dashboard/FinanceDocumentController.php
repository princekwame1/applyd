<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\FinanceDocument;
use App\Models\FinanceTransaction;
use App\Support\FinanceDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Invoices, receipts and anything else backing one entry. Files sit on the
 * private disk, so this controller is the only way to one — there is no public
 * URL to guess.
 */
class FinanceDocumentController extends Controller
{
    public function store(Request $request, FinanceTransaction $transaction)
    {
        $request->validate([
            'kind' => ['required', Rule::in(array_keys(FinanceDocument::KINDS))],
            'document' => ['required', 'file', 'mimes:'.FinanceDocument::MIMES, 'max:'.FinanceDocument::MAX_KB],
        ]);

        FinanceDocuments::store(
            $request->file('document'),
            $transaction,
            $request->input('kind'),
            $request->user()->id,
        );

        return redirect()
            ->route('dashboard.finance.transaction', $transaction)
            ->with('status', 'Document attached.');
    }

    public function download(FinanceDocument $document)
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }

    public function destroy(FinanceDocument $document)
    {
        $transaction = $document->transaction;

        FinanceDocuments::delete($document);

        return redirect()
            ->route('dashboard.finance.transaction', $transaction)
            ->with('status', 'Document removed.');
    }
}
