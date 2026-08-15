@php($isEdit = isset($model) && $model)
@php($rule = $isEdit ? $model->condition() : null)
@php($controllers = $controllers ?? collect())
<form method="POST"
      action="{{ $isEdit ? route('dashboard.questionnaires.questions.update', $model) : route('dashboard.questionnaires.questions.store', $questionnaire) }}"
      data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div class="span-2">
            <label class="field-label" for="q_label">What do you want to ask? <span class="req">*</span></label>
            <input type="text" id="q_label" name="label" value="{{ old('label', $model?->label) }}"
                   placeholder="e.g. What is your current job title?" required>
            <div class="upload-hint">Write it the way you'd say it out loud — this is exactly what people will read.</div>
            <div class="field-error" data-error="label">@error('label'){{ $message }}@enderror</div>
        </div>

        <div>
            <label class="field-label" for="q_type">How should people answer? <span class="req">*</span></label>
            <select id="q_type" name="type" required data-question-type>
                @foreach (App\Models\QuestionnaireQuestion::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $model?->type ?? 'short_text') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="field-error" data-error="type"></div>
        </div>

        @if ($isEdit)
            <div>
                <label class="field-label" for="q_key_display">Reference name</label>
                <input type="text" id="q_key_display" value="{{ $model->key }}" disabled>
                <div class="upload-hint">Answers are filed under this name, so it's fixed once the question exists.</div>
            </div>
        @else
            <div>
                <label class="field-label" for="q_key">Reference name <span class="req">*</span></label>
                <input type="text" id="q_key" name="key" value="{{ old('key') }}" placeholder="e.g. job_title" required>
                <div class="upload-hint">
                    A short, plain name for your own use — it heads the column in exports. Lower-case, no spaces.
                    Answers get filed under it, so choose one you'll be happy with: it can't be changed afterwards.
                </div>
                <div class="field-error" data-error="key"></div>
            </div>
        @endif

        <div class="span-2">
            <label class="field-label" for="q_help_text">Anything to explain? </label>
            <input type="text" id="q_help_text" name="help_text" value="{{ old('help_text', $model?->help_text) }}"
                   placeholder="e.g. Tell us roughly — an estimate is fine.">
            <div class="upload-hint">Optional. Shows as a small line under the question, for when the wording needs a nudge.</div>
            <div class="field-error" data-error="help_text"></div>
        </div>

        <div class="span-2" data-options-field hidden>
            <label class="field-label" for="q_options">The choices people pick from <span class="req">*</span></label>
            <textarea id="q_options" name="options" rows="5" style="width:100%; font-family:inherit;"
                      placeholder="One per line, like this:&#10;High School&#10;Diploma&#10;Bachelor's&#10;Master's&#10;PhD">{{ old('options', $model ? implode("\n", $model->optionList()) : '') }}</textarea>
            <div class="upload-hint">One per line, in the order you want them shown. Blank lines and repeats are ignored.</div>
            <div class="field-error" data-error="options">@error('options'){{ $message }}@enderror</div>
        </div>

        <div data-max-select-field hidden>
            <label class="field-label" for="q_max_select">Cap how many they can tick</label>
            <input type="number" id="q_max_select" name="max_select" min="1"
                   value="{{ old('max_select', $model?->maxSelect()) }}" placeholder="No cap">
            <div class="upload-hint">Leave this empty and people can tick as many as they like.</div>
            <div class="field-error" data-error="max_select"></div>
        </div>

        <div data-placeholder-field hidden>
            <label class="field-label" for="q_placeholder">Faint example text</label>
            <input type="text" id="q_placeholder" name="placeholder" value="{{ old('placeholder', $model?->placeholder) }}"
                   placeholder="e.g. Operations Manager">
            <div class="upload-hint">Optional. Sits greyed out inside the box until they start typing.</div>
            <div class="field-error" data-error="placeholder"></div>
        </div>

        <div data-file-field hidden>
            <label class="field-label" for="q_mimes">Which files will you accept?</label>
            <input type="text" id="q_mimes" name="mimes"
                   value="{{ old('mimes', $model?->isFile() ? $model->fileMimes() : App\Models\QuestionnaireQuestion::DEFAULT_FILE_MIMES) }}"
                   placeholder="{{ App\Models\QuestionnaireQuestion::DEFAULT_FILE_MIMES }}">
            <div class="upload-hint">Separate the file types with commas. Anything else gets turned away.</div>
            <div class="field-error" data-error="mimes"></div>
        </div>

        <div data-file-field hidden>
            <label class="field-label" for="q_max_kb">How big can it be?</label>
            <input type="number" id="q_max_kb" name="max_kb" min="64" max="20480"
                   value="{{ old('max_kb', $model?->isFile() ? $model->fileMaxKb() : App\Models\QuestionnaireQuestion::DEFAULT_FILE_MAX_KB) }}">
            <div class="upload-hint">In KB — 5120 is about 5 MB, which is plenty for a CV.</div>
            <div class="field-error" data-error="max_kb"></div>
        </div>

        <div>
            <label class="field-label">Do they have to answer?</label>
            <label class="switch-row">
                <input type="hidden" name="is_required" value="0">
                <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $model?->is_required ?? true))>
                <span>Yes <small>(they can't submit until they do)</small></span>
            </label>
        </div>

        <div>
            <label class="field-label">Is it ready?</label>
            <label class="switch-row">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $model?->is_active ?? true))>
                <span>Show it on the form <small>(turn off to park it out of sight)</small></span>
            </label>
        </div>

        {{-- Conditional logic. Folded away because most questions are simply
             always asked, and the panel shouldn't imply otherwise. --}}
        <details class="span-2 q-cond" @if ($rule) open @endif>
            <summary>Only ask this sometimes</summary>

            @if ($controllers->isEmpty())
                <p class="upload-hint" style="margin-top:10px;">
                    There's nothing earlier on this form to base it on yet. Add the question that comes first,
                    then come back — a question can only follow on from one that's already been answered.
                </p>
            @else
                <p class="upload-hint" style="margin:10px 0 12px;">
                    Skip this question for people it doesn't apply to. For example, only ask for a job title
                    once someone has said they're employed.
                </p>

                <div class="modal-grid" style="gap:14px;">
                    <div class="span-2">
                        <label class="field-label" for="q_condition_key">When should it come up?</label>
                        <select id="q_condition_key" name="condition_key" data-no-select2 data-condition-key>
                            <option value="">Always — ask everyone this question</option>
                            @foreach ($controllers as $candidate)
                                <option value="{{ $candidate->key }}"
                                        @selected(old('condition_key', $rule['key'] ?? '') === $candidate->key)>
                                    Only when they answer “{{ Str::limit($candidate->label, 60) }}”
                                </option>
                            @endforeach
                        </select>
                        <div class="field-error" data-error="condition_key">@error('condition_key'){{ $message }}@enderror</div>
                    </div>

                    <div class="span-2" data-condition-detail hidden>
                        <label class="field-label" for="q_condition_operator">And that answer…</label>
                        <select id="q_condition_operator" name="condition_operator" data-no-select2 data-condition-operator>
                            @foreach (App\Models\QuestionnaireQuestion::OPERATORS as $value => $label)
                                <option value="{{ $value }}" @selected(old('condition_operator', $rule['operator'] ?? 'in') === $value)>{{ $label }}…</option>
                            @endforeach
                        </select>
                        <div class="field-error" data-error="condition_operator"></div>
                    </div>

                    <div class="span-2" data-condition-values hidden>
                        <label class="field-label" for="q_condition_values">…one of these</label>
                        {{-- Deliberately not Select2'd: the option list is rewritten
                             by script whenever the question above changes. --}}
                        <select id="q_condition_values" name="condition_values[]" multiple size="6"
                                data-no-select2 data-condition-value-list style="width:100%;">
                            @foreach ($controllers as $candidate)
                                @foreach ($candidate->optionList() as $option)
                                    <option value="{{ $option }}" data-for="{{ $candidate->key }}"
                                            @selected(old('condition_key', $rule['key'] ?? '') === $candidate->key
                                                && in_array($option, old('condition_values', $rule['values'] ?? []), true))>{{ $option }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        <div class="upload-hint">Hold ⌘ / Ctrl to pick more than one. Any of them brings the question up.</div>
                        <div class="field-error" data-error="condition_values">@error('condition_values'){{ $message }}@enderror</div>
                    </div>

                    <div class="span-2" data-condition-freetext hidden>
                        <div class="upload-hint">
                            That question is typed in rather than picked from a list, so the only rule that
                            makes sense is “has any answer”.
                        </div>
                    </div>
                </div>
            @endif
        </details>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save changes' : 'Add question' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
