@extends('layouts.company')

@section('title', 'My Jobs — '.$company->name)

@section('content')
<div class="page-head">
    <h1 class="section-title">{{ $company->name }}</h1>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

{{-- Where they stand, said plainly. A recruiter whose adverts aren't showing
     and who is told nothing assumes the site is broken. --}}
@if ($company->isPending())
    <div class="verify-note verify-pending">
        <strong>We are checking your details.</strong>
        We confirm the Ghana Card of every job poster before their adverts reach the public board — it is what keeps
        fake employers off the site. Nothing is on hold for you here: post your jobs now and each one goes live as
        soon as both checks are done. We will email {{ $company->user?->email }} the moment you are verified.
    </div>
@elseif ($company->isRejected())
    <div class="verify-note verify-rejected">
        <strong>We could not verify this account yet.</strong>
        @if ($company->review_note)
            <blockquote style="margin:10px 0; padding:10px 14px; background:#fff; border-left:3px solid var(--brand); border-radius:6px;">{{ $company->review_note }}</blockquote>
        @endif
        Your postings stay off the public board until this is settled, but nothing you have written has been deleted.
        Reply to the email we sent with what is needed and we will take another look.
    </div>
@endif

<div class="stat-cards">
    <div class="stat-card"><div class="num">{{ $openings->count() }}</div><div class="lbl">Job Postings</div></div>
    <div class="stat-card"><div class="num">{{ $openings->where('is_open', true)->count() }}</div><div class="lbl">Open Positions</div></div>
    <div class="stat-card"><div class="num">{{ $openings->sum('applications_count') }}</div><div class="lbl">Total Applications</div></div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <h3 style="margin-bottom: 14px;">Post a New Job</h3>
    <form method="POST" action="{{ route('company.jobs.store') }}" style="display:grid; gap:14px;">
        @csrf
        <div class="form-grid" style="gap: 14px 16px;">
            <div>
                <label class="field-label" for="title">Job Title <span class="req">*</span></label>
                <input type="text" id="title" name="title" placeholder="e.g. Digital Marketing Assistant" value="{{ old('title') }}" required>
                @error('title') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="type">Type <span class="req">*</span></label>
                <select id="type" name="type" required>
                    @foreach (\App\Models\JobOpening::TYPES as $type)
                        <option value="{{ $type }}" @selected(old('type') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                @error('type') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="sector">Sector</label>
                <select id="sector" name="sector">
                    <option value="">Select a sector…</option>
                    @foreach (\App\Models\JobOpening::SECTORS as $sector)
                        <option value="{{ $sector }}" @selected(old('sector') === $sector)>{{ $sector }}</option>
                    @endforeach
                </select>
                @error('sector') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="location">Location</label>
                <input type="text" id="location" name="location" placeholder="e.g. Accra / Remote" value="{{ old('location') }}">
                @error('location') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="salary_range">Salary Range</label>
                <input type="text" id="salary_range" name="salary_range" placeholder="e.g. GHS 3,000–4,500 / month" value="{{ old('salary_range') }}">
                @error('salary_range') <div class="field-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="field-label" for="deadline">Application Deadline</label>
                <input type="date" id="deadline" name="deadline" value="{{ old('deadline') }}">
                @error('deadline') <div class="field-error">{{ $message }}</div> @enderror
            </div>
        </div>
        <div>
            <label class="field-label" for="description">Description <span class="req">*</span></label>
            <textarea id="description" name="description" rows="5" data-rich placeholder="Role, responsibilities, requirements, how to stand out…" style="width:100%; padding:10px 14px; border:1.5px solid #cbd5e1; border-radius:10px; font-size:1rem; font-family:inherit;" required>{{ old('description') }}</textarea>
            @error('description') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <button type="submit" class="btn btn-brand btn-sm">Publish Job</button>
        </div>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom: 14px;">Your Job Postings</h3>
    <div class="table-wrap" style="box-shadow:none;">
        <table class="nice">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Applications</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($openings as $opening)
                    <tr>
                        <td><strong>{{ $opening->title }}</strong>{{ $opening->location ? ' · '.$opening->location : '' }}</td>
                        <td>{{ $opening->type }}</td>
                        <td>{{ $opening->deadline?->format('M j, Y') ?? '—' }}</td>
                        <td>
                            @if ($opening->is_live)
                                <span class="badge badge-yes">Live</span>
                            @elseif ($opening->isRejected())
                                <span class="badge badge-no" title="{{ $opening->review_note }}">Rejected</span>
                            @elseif (! $opening->isApproved())
                                <span class="status-chip status-pending">In review</span>
                            @elseif (! $company->isApproved())
                                <span class="status-chip status-pending">Awaiting verification</span>
                            @elseif (! $opening->is_accepting)
                                <span class="badge badge-no">Closed</span>
                            @else
                                <span class="badge badge-no">Not live</span>
                            @endif
                        </td>
                        {{-- Always a link, plan or no plan: the count is what tells a
                             recruiter there is something worth paying for, and the
                             page itself sends them to the plans screen. --}}
                        <td>
                            <a class="app-count @if (! $hasPlan) is-locked @endif"
                               href="{{ route('company.applications', $opening) }}"
                               @if (! $hasPlan) title="Reviewing applicants needs a plan" @endif>
                                {{ $opening->applications_count }} {{ Str::plural('application', $opening->applications_count) }}
                                @if (! $hasPlan)<i class="fa-solid fa-lock" aria-hidden="true"></i>@endif
                            </a>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('jobs.show', $opening) }}" target="_blank" title="View" aria-label="View"><i class="fa-solid fa-eye"></i></a>
                                <a href="{{ route('company.jobs.edit', $opening) }}" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                <form method="POST" action="{{ route('company.jobs.destroy', $opening) }}" data-confirm="Delete this job and all its applications?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="link-danger" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center; color:var(--ink-soft); padding: 40px;">No job postings yet — publish your first one above.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@include('partials.quill')
@endsection
