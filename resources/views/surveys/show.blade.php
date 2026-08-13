@extends('layouts.app')

@section('title', $survey->name.' — Pulse Check')

@section('content')
@php
    $total = $questions->count();
    // Rising "signal bar" progress meter — height climbs across the set.
    $barHeight = fn (int $i) => 9 + ($total > 1 ? (int) round($i / ($total - 1) * 13) : 13);
@endphp

<section class="pulse-wrap">
    <div class="container pulse-narrow">

        <div class="pulse-topbar">
            <a class="pulse-back" href="{{ route('surveys.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                Exit
            </a>
            <span class="pulse-bars" id="pulseBars" aria-hidden="true">
                @for ($i = 0; $i < $total; $i++)
                    <span class="{{ $i === 0 ? 'on' : '' }}" style="height: {{ $barHeight($i) }}px;"></span>
                @endfor
            </span>
        </div>

        <div class="pulse-step-meta" id="pulseStepMeta">
            {{ strtoupper($survey->name) }} · {{ $total }} {{ Str::plural('QUESTION', $total) }}
        </div>

        @if ($errors->any())
            <div class="error-box">Please check your answers below.</div>
        @endif

        <form method="POST" action="{{ route('surveys.store', $survey) }}" class="pulse-form" id="pulseForm" novalidate>
            @csrf

            @foreach ($questions as $index => $question)
                @php
                    $name = 'answers['.$question->key.']';
                    $oldKey = 'answers.'.$question->key;
                    $value = old($oldKey);
                @endphp

                <div class="pulse-step {{ $index === 0 ? 'active' : '' }}" data-step="{{ $index + 1 }}">
                    <h2 class="pulse-question">
                        {{ $question->prompt }}
                        @unless ($question->required)<span class="pulse-optional">optional</span>@endunless
                    </h2>

                    @if ($question->type === 'choice')
                        <div class="pulse-chips">
                            @foreach ($question->optionList() as $option)
                                <label class="pulse-chip">
                                    <input type="radio" name="{{ $name }}" value="{{ $option }}"
                                           @checked((string) $value === (string) $option)
                                           @if ($question->required) required @endif>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>

                    @elseif ($question->type === 'scale')
                        <div class="pulse-scale">
                            @foreach ($question->optionList() as $i => $label)
                                <label class="pulse-dot" title="{{ $label }}">
                                    <input type="radio" name="{{ $name }}" value="{{ $i + 1 }}"
                                           @checked((string) $value === (string) ($i + 1))
                                           @if ($question->required) required @endif>
                                    <span>{{ $i + 1 }}</span>
                                </label>
                            @endforeach
                        </div>
                        <div class="pulse-scale-ends">
                            <span>{{ $question->optionList()[0] ?? '' }}</span>
                            <span>{{ $question->optionList()[count($question->optionList()) - 1] ?? '' }}</span>
                        </div>

                    @else
                        <textarea name="{{ $name }}" rows="4" class="pulse-text"
                                  placeholder="{{ $question->placeholder ?: 'Type your answer…' }}"
                                  @if ($question->required) required @endif>{{ $value }}</textarea>
                    @endif

                    <div class="field-error" @error($oldKey) @else hidden @enderror>@error($oldKey){{ $message }}@enderror</div>
                </div>
            @endforeach

            <div class="pulse-actions">
                <button type="button" class="btn btn-outline btn-sm" id="pulseBack" hidden>Back</button>
                <button type="button" class="btn btn-brand pulse-advance" id="pulseNext" hidden>Next</button>
                <button type="submit" class="btn btn-brand pulse-advance" id="pulseSubmit">Submit</button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('pulseForm');
    if (!form) return;

    var steps = Array.prototype.slice.call(form.querySelectorAll('.pulse-step'));
    var total = steps.length;
    if (!total) return;

    var bars = document.getElementById('pulseBars');
    var meta = document.getElementById('pulseStepMeta');
    var backBtn = document.getElementById('pulseBack');
    var nextBtn = document.getElementById('pulseNext');
    var submitBtn = document.getElementById('pulseSubmit');
    var surveyLabel = @json(strtoupper($survey->name));
    var current = 1;

    // Only now do the steps become one-at-a-time — without JS every question
    // stays on the page and the form submits in a single go.
    form.classList.add('is-stepped');
    nextBtn.hidden = false;

    function show(n) {
        current = n;
        steps.forEach(function (step) {
            step.classList.toggle('active', Number(step.dataset.step) === n);
        });
        if (bars) {
            Array.prototype.forEach.call(bars.children, function (bar, i) {
                bar.classList.toggle('on', i < n);
            });
        }
        if (meta) meta.textContent = surveyLabel + ' · Q' + n + ' OF ' + total;
        backBtn.hidden = n === 1;
        nextBtn.hidden = n === total;
        submitBtn.hidden = n !== total;
    }

    function fieldsIn(n) {
        var step = steps[n - 1];
        return step ? Array.prototype.slice.call(step.querySelectorAll('input, textarea')) : [];
    }

    function valid(n) {
        var fields = fieldsIn(n);
        var errorSlot = steps[n - 1].querySelector('.field-error');
        var ok = fields.every(function (f) { return f.checkValidity(); });

        if (errorSlot) {
            errorSlot.textContent = ok ? '' : 'Please answer this one before moving on.';
            errorSlot.hidden = ok;
        }
        if (!ok) {
            var first = fields.find(function (f) { return !f.checkValidity(); });
            if (first) first.focus();
        }
        return ok;
    }

    nextBtn.addEventListener('click', function () {
        if (valid(current)) show(current + 1);
    });

    backBtn.addEventListener('click', function () {
        if (current > 1) show(current - 1);
    });

    form.addEventListener('submit', function (e) {
        // The browser's own validation is off (novalidate) so the stepper owns
        // the messaging — re-check every step before letting the post through.
        for (var n = 1; n <= total; n++) {
            if (!valid(n)) {
                e.preventDefault();
                show(n);
                return;
            }
        }
    });

    // Picking an option clears the inline error and, for single-tap questions,
    // moves straight on — the whole point of one question per screen.
    form.addEventListener('change', function (e) {
        if (e.target.type !== 'radio') return;
        var step = e.target.closest('.pulse-step');
        if (!step) return;
        var slot = step.querySelector('.field-error');
        if (slot) { slot.textContent = ''; slot.hidden = true; }
        if (Number(step.dataset.step) === current && current < total) {
            setTimeout(function () { show(current + 1); }, 180);
        }
    });

    // A server-side rejection re-renders the page: open the first bad step.
    var failed = form.querySelector('.pulse-step .field-error:not([hidden])');
    show(failed ? Number(failed.closest('.pulse-step').dataset.step) : 1);
})();
</script>
@endpush
