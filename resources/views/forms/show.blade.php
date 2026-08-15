@extends('layouts.app')

@section('title', $questionnaire->title.' — Applyd Academy')

@section('content')
@php($hasUpload = $questions->contains(fn ($q) => $q->isFile()))

<section class="qform-wrap">
    <div class="container qform-narrow">

        <div class="qform-head">
            <h1 class="qform-title">{{ $questionnaire->title }}</h1>
            @if ($questionnaire->description)
                <p class="qform-lead">{{ $questionnaire->description }}</p>
            @endif
            @if ($questionnaire->closes_at)
                <p class="qform-deadline">Open until {{ $questionnaire->closes_at->format('M j, Y') }}</p>
            @endif
        </div>

        @if (session('form_closed'))
            <div class="error-box">{{ session('form_closed') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-box">Please check the highlighted answers below.</div>
        @endif

        <form method="POST" action="{{ route('forms.store', $questionnaire) }}" class="qform"
              @if ($hasUpload) enctype="multipart/form-data" @endif>
            @csrf

            @foreach ($questions as $question)
                @php($path = $question->inputPath())
                @php($name = $question->inputName())
                @php($value = old($path))
                @php($id = 'q_'.$question->key)

                <div class="qform-field @error($path) has-error @enderror"
                     data-question-key="{{ $question->key }}"
                     @if ($question->isConditional()) data-visible-when="{{ json_encode($question->condition()) }}" @endif>
                    <label class="qform-label" for="{{ $id }}">
                        {{ $question->label }}
                        @if ($question->is_required)
                            <span class="req">*</span>
                        @else
                            <span class="qform-optional">optional</span>
                        @endif
                    </label>

                    @if ($question->help_text)
                        <p class="qform-help">{{ $question->help_text }}</p>
                    @endif

                    @switch ($question->type)
                        @case ('radio')
                            <div class="qform-choices" role="radiogroup" aria-labelledby="{{ $id }}">
                                @foreach ($question->optionList() as $i => $option)
                                    <label class="qform-choice">
                                        <input type="radio" id="{{ $i === 0 ? $id : $id.'_'.$i }}" name="{{ $name }}" value="{{ $option }}"
                                               @checked((string) $value === (string) $option)
                                               @if ($question->is_required) required @endif>
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @break

                        @case ('checkbox')
                            @php($checked = is_array($value) ? $value : [])
                            <div class="qform-choices">
                                @foreach ($question->optionList() as $i => $option)
                                    <label class="qform-choice">
                                        <input type="checkbox" id="{{ $i === 0 ? $id : $id.'_'.$i }}" name="{{ $name }}" value="{{ $option }}"
                                               @checked(in_array($option, $checked, true))>
                                        <span>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @if ($question->maxSelect())
                                <p class="qform-help">Pick up to {{ $question->maxSelect() }}.</p>
                            @endif
                            @break

                        @case ('select')
                            <select id="{{ $id }}" name="{{ $name }}" @if ($question->is_required) required @endif>
                                <option value="">Choose one…</option>
                                @foreach ($question->optionList() as $option)
                                    <option value="{{ $option }}" @selected((string) $value === (string) $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            @break

                        @case ('long_text')
                            <textarea id="{{ $id }}" name="{{ $name }}" rows="5"
                                      placeholder="{{ $question->placeholder }}"
                                      @if ($question->is_required) required @endif>{{ $value }}</textarea>
                            @break

                        @case ('file')
                            <input type="file" id="{{ $id }}" name="{{ $name }}"
                                   accept="{{ collect(explode(',', $question->fileMimes()))->map(fn ($e) => '.'.trim($e))->implode(',') }}"
                                   @if ($question->is_required) required @endif>
                            <p class="qform-help">
                                {{ strtoupper(str_replace(',', ', ', $question->fileMimes())) }} —
                                up to {{ round($question->fileMaxKb() / 1024, 1) }} MB.
                            </p>
                            @break

                        @case ('number')
                            <input type="number" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" step="any"
                                   placeholder="{{ $question->placeholder }}" @if ($question->is_required) required @endif>
                            @break

                        @case ('email')
                            <input type="email" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
                                   placeholder="{{ $question->placeholder }}" @if ($question->is_required) required @endif>
                            @break

                        @case ('phone')
                            <input type="tel" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
                                   placeholder="{{ $question->placeholder }}" @if ($question->is_required) required @endif>
                            @break

                        @case ('date')
                            <input type="date" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
                                   @if ($question->is_required) required @endif>
                            @break

                        @default
                            <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
                                   placeholder="{{ $question->placeholder }}" @if ($question->is_required) required @endif>
                    @endswitch

                    @error($path)
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                    @error($path.'.*')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>
            @endforeach

            <div class="qform-actions">
                <button type="submit" class="btn btn-brand">{{ $questionnaire->submit_text }}</button>
                <p class="qform-note">Your answers go straight to Applyd Academy.</p>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    // Conditional questions. Without this script every question simply shows —
    // the server decides all over again which ones were actually being asked,
    // ignores answers to the rest, and never requires them. So this is polish,
    // not the rule: it just spares people questions that don't apply to them.
    var form = document.querySelector('.qform');
    if (!form) return;

    var fields = Array.prototype.slice.call(form.querySelectorAll('[data-question-key]'));
    var conditional = fields.filter(function (f) { return f.hasAttribute('data-visible-when'); });
    if (!conditional.length) return;

    var byKey = {};
    fields.forEach(function (f) { byKey[f.dataset.questionKey] = f; });

    /** Everything currently ticked, typed or picked for one question. */
    function answersFor(field) {
        if (!field) return [];

        var checked = field.querySelectorAll('input[type=radio]:checked, input[type=checkbox]:checked');
        if (checked.length) return Array.prototype.map.call(checked, function (i) { return i.value; });

        // A hidden question has no answer, whatever is still typed into it.
        if (field.hidden) return [];

        var single = field.querySelector('select, textarea, input:not([type=radio]):not([type=checkbox])');
        return single && single.value !== '' ? [single.value] : [];
    }

    function met(rule, values) {
        if (rule.operator === 'answered') return values.length > 0;

        var hit = values.some(function (v) { return rule.values.indexOf(v) !== -1; });

        return rule.operator === 'not_in' ? !hit : hit;
    }

    function sync() {
        // In document order, so a rule pointing at an earlier question always
        // reads a state that has already been settled this pass.
        conditional.forEach(function (field) {
            var rule = JSON.parse(field.dataset.visibleWhen);
            var controller = byKey[rule.key];
            var show = !!controller && !controller.hidden && met(rule, answersFor(controller));

            if (field.hidden === !show) return;

            field.hidden = !show;

            // Disabled, not just hidden: a hidden `required` control blocks
            // submission with an error the visitor can never see or fix, and a
            // disabled one isn't posted at all.
            field.querySelectorAll('input, select, textarea').forEach(function (input) {
                input.disabled = !show;
            });
        });
    }

    form.addEventListener('change', sync);
    form.addEventListener('input', sync);
    sync();
})();
</script>
@endpush
