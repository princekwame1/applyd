@extends('layouts.admin')

@use('App\Models\FinanceDocument')
@use('App\Support\Finance')

@section('title', $transaction->reference.' — Finance')

@section('content')
@php($maxMb = round(FinanceDocument::MAX_KB / 1024))
@php($accept = collect(explode(',', FinanceDocument::MIMES))->map(fn ($e) => '.'.$e)->implode(','))

<div class="page-head">
    <h1 class="section-title">{{ $transaction->reference }}</h1>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.finance.edit', $transaction) }}"
           data-modal-open data-modal-url="{{ route('dashboard.finance.edit', $transaction) }}" data-modal-title="Edit entry">Edit</a>
        <a class="btn btn-sm btn-outline" href="{{ route('dashboard.finance') }}">Back to Finance</a>
    </div>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div class="error-box">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="error-box">{{ $errors->first() }}</div>
@endif

<div class="card sv-flat" style="margin-bottom:22px;">
    <div class="fin-headline">
        <div class="fin-headline-amount {{ $transaction->isIncome() ? 'fin-in' : 'fin-out' }}">
            {{ $transaction->isIncome() ? '+' : '−' }}{{ Finance::money($transaction->amount) }}
        </div>
        <div class="fin-headline-meta">
            <span class="badge {{ $transaction->isIncome() ? 'badge-yes' : 'badge-out' }}">{{ $transaction->typeLabel() }}</span>
            <span>{{ $transaction->occurred_on->format('l, j F Y') }}</span>
        </div>
    </div>

    <div class="detail-grid" style="margin-top:20px;">
        <div class="detail-item">
            <div class="lbl">Category</div>
            <div class="val">{{ $transaction->category?->name ?? 'Uncategorised' }}</div>
        </div>
        <div class="detail-item">
            <div class="lbl">{{ $transaction->isIncome() ? 'Received from' : 'Paid to' }}</div>
            <div class="val">{{ $transaction->party ?: '—' }}</div>
        </div>
        <div class="detail-item">
            <div class="lbl">Method</div>
            <div class="val">{{ $transaction->method ?: '—' }}</div>
        </div>
        <div class="detail-item">
            <div class="lbl">Their document no.</div>
            <div class="val">{{ $transaction->document_no ?: '—' }}</div>
        </div>
        <div class="detail-item">
            <div class="lbl">Recorded by</div>
            <div class="val">{{ $transaction->recorder?->name ?? 'Unknown' }} · {{ $transaction->created_at->format('M j, Y g:ia') }}</div>
        </div>
        @if ($transaction->note)
            <div class="detail-item" style="grid-column: 1 / -1;">
                <div class="lbl">Note</div>
                <div class="val" style="white-space:pre-line;">{{ $transaction->note }}</div>
            </div>
        @endif
    </div>
</div>

<div class="card sv-flat">
    <div class="card-head">
        <h2>Invoices &amp; receipts</h2>
    </div>

    @forelse ($transaction->documents as $document)
        <div class="fin-doc">
            <span class="fin-doc-kind fin-doc-{{ $document->kind }}">{{ $document->kindLabel() }}</span>
            <a class="fin-doc-name" href="{{ route('dashboard.finance.document', $document) }}">{{ $document->original_name }}</a>
            <span class="fin-doc-meta">
                {{ $document->humanSize() }}
                @if ($document->uploader) · added by {{ $document->uploader->name }} @endif
                · {{ $document->created_at->format('M j, Y') }}
            </span>
            <form method="POST" action="{{ route('dashboard.finance.document.destroy', $document) }}"
                  data-confirm="Remove {{ $document->original_name }}? The file is deleted for good.">
                @csrf
                @method('DELETE')
                <button type="submit" class="link-danger" title="Remove" aria-label="Remove"><i class="fa-solid fa-trash"></i></button>
            </form>
        </div>
    @empty
        <p class="sv-empty" style="margin-bottom:18px;">
            Nothing attached yet. Anything you upload here is stored privately — it never gets a public link.
        </p>
    @endforelse

    <form method="POST" action="{{ route('dashboard.finance.documents.store', $transaction) }}"
          enctype="multipart/form-data" class="fin-doc-add">
        @csrf
        <div>
            <label class="field-label" for="d_kind">What is it?</label>
            <select id="d_kind" name="kind" required>
                @foreach (FinanceDocument::KINDS as $value => $label)
                    <option value="{{ $value }}" @selected(old('kind') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1 1 260px;">
            <label class="field-label" for="d_document">Choose a file</label>
            <input type="file" id="d_document" name="document" accept="{{ $accept }}" required>
            <div class="upload-hint">
                PDF, image or Office file, up to {{ $maxMb }} MB.
                Uploading an invoice or receipt replaces the one already there.
            </div>
        </div>
        <button type="submit" class="btn btn-brand btn-sm">Attach</button>
    </form>
</div>
@endsection
