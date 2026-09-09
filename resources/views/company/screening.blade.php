@extends('layouts.company')

@section('title', 'Screening — '.$opening->title)

@php use App\Models\JobScreeningCriterion as Criterion; @endphp

@section('content')
<a href="{{ route('company.applications', $opening) }}" class="tool-link">← Applications</a>
<div class="page-head" style="margin-top: 8px;">
    <h1 class="section-title">Screening: {{ $opening->title }}</h1>
    <span class="tag">{{ $applicants }} {{ Str::plural('applicant', $applicants) }}</span>
</div>

@if (session('status'))
    <div class="success-box">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="error-box">{{ $errors->first() }}</div>
@endif

<div class="card" style="margin-bottom: 24px;">
    <h3 style="margin-bottom: 8px;">What this role asks for</h3>
    <p style="color: var(--ink-soft); font-size: .93rem;">
        Every application is scored against this list the moment it arrives, and the applicants screen puts the
        best fit first. <strong>Skills</strong> are looked for in the CV and cover letter and are never shown to
        candidates. <strong>Questions</strong> are asked on the application form. Mark the things you cannot do
        without as must-haves — an applicant who misses one is flagged for you, never turned away.
    </p>

    @if ($criteria->isEmpty())
        <p style="color: var(--ink-soft); margin-top: 14px;">Nothing set yet — add your first criterion below.</p>
    @else
        <div class="table-wrap" style="box-shadow:none; margin-top: 16px;">
            <table class="nice">
                <thead>
                    <tr>
                        <th>Criterion</th>
                        <th>Kind</th>
                        <th>Passes when</th>
                        <th>Weight</th>
                        <th>Must-have</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($criteria as $criterion)
                        <tr>
                            <td><strong>{{ $criterion->label }}</strong></td>
                            <td>{{ $criterion->isKeyword() ? 'Skill in CV' : 'Question' }}</td>
                            <td>{{ $criterion->expectation_label }}</td>
                            <td>×{{ $criterion->weight }}</td>
                            <td>{!! $criterion->is_knockout ? '<span class="badge badge-yes">Yes</span>' : '<span class="badge badge-no">No</span>' !!}</td>
                            <td style="white-space: nowrap;">
                                <button type="button" class="btn btn-sm btn-outline" data-edit-criterion="{{ $criterion->id }}">Edit</button>
                                <form method="POST" action="{{ route('company.screening.destroy', [$opening, $criterion]) }}"
                                      style="display:inline;"
                                      data-confirm="Remove “{{ $criterion->label }}” and re-score everyone against what is left?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Edit forms, one per row. Hidden until asked for — the key, the kind and
     the answer type are absent on purpose: answers are filed under those and
     moving them would re-interpret every application already collected. --}}
@foreach ($criteria as $criterion)
    <div class="card criterion-edit" id="criterion-{{ $criterion->id }}" hidden>
        <h3 style="margin-bottom: 14px;">Edit: {{ $criterion->label }}</h3>
        <form method="POST" action="{{ route('company.screening.update', [$opening, $criterion]) }}" style="display:grid; gap:14px;">
            @csrf
            @method('PUT')
            <div class="form-grid" style="gap: 14px 16px;">
                <div>
                    <label class="field-label">{{ $criterion->isKeyword() ? 'Skill or keyword' : 'Question' }} <span class="req">*</span></label>
                    <input type="text" name="label" value="{{ $criterion->label }}" maxlength="160" required>
                </div>
                <div>
                    <label class="field-label">Weight</label>
                    <select name="weight" data-no-select2>
                        @for ($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" @selected($criterion->weight === $i)>×{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                @if ($criterion->answer_type === Criterion::NUMBER)
                    <div>
                        <label class="field-label">Passes at or above</label>
                        <input type="number" name="minimum" min="0" max="99" step="0.5" value="{{ $criterion->expected[0] ?? 0 }}" required>
                    </div>
                @endif
                @if ($criterion->answer_type === Criterion::CHOICE)
                    <div>
                        <label class="field-label">Options <small>(one per line)</small></label>
                        <textarea name="choices" rows="4" required>{{ implode("\n", $criterion->options ?: []) }}</textarea>
                    </div>
                    <div>
                        <label class="field-label">Accepted answers <small>(one per line)</small></label>
                        <textarea name="accepted" rows="4" required>{{ implode("\n", $criterion->expected ?: []) }}</textarea>
                    </div>
                @endif
            </div>
            <label class="check-line">
                <input type="checkbox" name="is_knockout" value="1" data-no-select2 @checked($criterion->is_knockout)>
                Must-have — flag anyone who misses this
            </label>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-brand btn-sm">Save changes</button>
                <button type="button" class="btn btn-outline btn-sm" data-cancel-criterion="{{ $criterion->id }}">Cancel</button>
            </div>
        </form>
    </div>
@endforeach

<div class="card">
    <h3 style="margin-bottom: 14px;">Add a criterion</h3>
    <form method="POST" action="{{ route('company.screening.store', $opening) }}" style="display:grid; gap:14px;" id="addCriterion">
        @csrf
        <div class="form-grid" style="gap: 14px 16px;">
            <div>
                <label class="field-label" for="kind">Kind <span class="req">*</span></label>
                <select id="kind" name="kind" data-no-select2 required>
                    <option value="{{ Criterion::KEYWORD }}">Skill to look for in the CV</option>
                    <option value="{{ Criterion::QUESTION }}">Question to ask the applicant</option>
                </select>
            </div>
            <div>
                <label class="field-label" for="label">Wording <span class="req">*</span></label>
                <input type="text" id="label" name="label" maxlength="160" required
                       value="{{ old('label') }}" placeholder="e.g. Laravel  —  or  —  Do you have a valid driver’s licence?">
            </div>
            <div data-when="question">
                <label class="field-label" for="answer_type">Answered with</label>
                <select id="answer_type" name="answer_type" data-no-select2>
                    <option value="{{ Criterion::YES_NO }}">Yes / No — passes on Yes</option>
                    <option value="{{ Criterion::NUMBER }}">A number — passes at or above a minimum</option>
                    <option value="{{ Criterion::CHOICE }}">A choice from a list</option>
                </select>
            </div>
            <div data-when="number" hidden>
                <label class="field-label" for="minimum">Passes at or above</label>
                <input type="number" id="minimum" name="minimum" min="0" max="99" step="0.5" value="{{ old('minimum', 1) }}">
            </div>
            <div data-when="choice" hidden>
                <label class="field-label" for="choices">Options <small>(one per line)</small></label>
                <textarea id="choices" name="choices" rows="4" placeholder="Degree&#10;HND&#10;Diploma&#10;None of these">{{ old('choices') }}</textarea>
            </div>
            <div data-when="choice" hidden>
                <label class="field-label" for="accepted">Accepted answers <small>(one per line, spelled as above)</small></label>
                <textarea id="accepted" name="accepted" rows="4" placeholder="Degree&#10;HND">{{ old('accepted') }}</textarea>
            </div>
            <div>
                <label class="field-label" for="weight">Weight</label>
                <select id="weight" name="weight" data-no-select2>
                    @for ($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" @selected((int) old('weight', 1) === $i)>×{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>
        <label class="check-line">
            <input type="checkbox" name="is_knockout" value="1" @checked(old('is_knockout'))>
            Must-have — flag anyone who misses this
        </label>
        <div>
            <button type="submit" class="btn btn-brand btn-sm">Add criterion</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('addCriterion');
        var kind = document.getElementById('kind');
        var answer = document.getElementById('answer_type');

        function show() {
            var isQuestion = kind.value === 'question';
            var type = answer.value;

            form.querySelectorAll('[data-when]').forEach(function (block) {
                var when = block.dataset.when;
                var on = when === 'question' ? isQuestion
                    : when === 'number' ? (isQuestion && type === 'number')
                    : (isQuestion && type === 'choice');

                block.hidden = !on;
                // A hidden control that is still submitted is a value the
                // recruiter never chose; disabling leaves it out entirely.
                block.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = !on;
                });
            });
        }

        kind.addEventListener('change', show);
        answer.addEventListener('change', show);
        show();

        document.addEventListener('click', function (e) {
            var open = e.target.closest('[data-edit-criterion]');
            var cancel = e.target.closest('[data-cancel-criterion]');

            if (open) {
                var panel = document.getElementById('criterion-' + open.dataset.editCriterion);
                panel.hidden = false;
                panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            if (cancel) {
                document.getElementById('criterion-' + cancel.dataset.cancelCriterion).hidden = true;
            }
        });
    })();
</script>
@endpush
