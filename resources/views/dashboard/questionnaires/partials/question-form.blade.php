@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.questionnaires.questions.update', $model) : route('dashboard.questionnaires.questions.store', $questionnaire) }}"
      data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div class="span-2">
            <label class="field-label" for="q_label">Question <span class="req">*</span></label>
            <input type="text" id="q_label" name="label" value="{{ old('label', $model?->label) }}"
                   placeholder="e.g. Which sessions do you want to attend?" required>
            <div class="field-error" data-error="label">@error('label'){{ $message }}@enderror</div>
        </div>

        <div>
            <label class="field-label" for="q_type">Answer type <span class="req">*</span></label>
            <select id="q_type" name="type" required data-question-type>
                @foreach (App\Models\QuestionnaireQuestion::TYPES as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', $model?->type ?? 'short_text') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="field-error" data-error="type"></div>
        </div>

        @if ($isEdit)
            <div>
                <label class="field-label" for="q_key_display">Key</label>
                <input type="text" id="q_key_display" value="{{ $model->key }}" disabled>
                <div class="upload-hint">Answers are filed under this key, so it can't be changed.</div>
            </div>
        @else
            <div>
                <label class="field-label" for="q_key">Key <span class="req">*</span></label>
                <input type="text" id="q_key" name="key" value="{{ old('key') }}" placeholder="e.g. sessions" required>
                <div class="upload-hint">Lower-case, no spaces. Answers are stored under this key, so it can't be changed later.</div>
                <div class="field-error" data-error="key"></div>
            </div>
        @endif

        <div class="span-2">
            <label class="field-label" for="q_help_text">Helper text</label>
            <input type="text" id="q_help_text" name="help_text" value="{{ old('help_text', $model?->help_text) }}"
                   placeholder="Optional line under the question.">
            <div class="field-error" data-error="help_text"></div>
        </div>

        <div class="span-2" data-options-field hidden>
            <label class="field-label" for="q_options">Options <span class="req">*</span></label>
            <textarea id="q_options" name="options" rows="5" style="width:100%; font-family:inherit;"
                      placeholder="One per line.&#10;Morning session&#10;Afternoon session&#10;Evening session">{{ old('options', $model ? implode("\n", $model->optionList()) : '') }}</textarea>
            <div class="upload-hint">One per line — these are the choices people pick from.</div>
            <div class="field-error" data-error="options">@error('options'){{ $message }}@enderror</div>
        </div>

        <div data-max-select-field hidden>
            <label class="field-label" for="q_max_select">Most they can tick</label>
            <input type="number" id="q_max_select" name="max_select" min="1"
                   value="{{ old('max_select', $model?->maxSelect()) }}" placeholder="No limit">
            <div class="upload-hint">Leave empty to allow every box.</div>
            <div class="field-error" data-error="max_select"></div>
        </div>

        <div data-placeholder-field hidden>
            <label class="field-label" for="q_placeholder">Placeholder</label>
            <input type="text" id="q_placeholder" name="placeholder" value="{{ old('placeholder', $model?->placeholder) }}"
                   placeholder="Grey hint inside the box">
            <div class="field-error" data-error="placeholder"></div>
        </div>

        <div data-file-field hidden>
            <label class="field-label" for="q_mimes">Allowed file types</label>
            <input type="text" id="q_mimes" name="mimes"
                   value="{{ old('mimes', $model?->isFile() ? $model->fileMimes() : App\Models\QuestionnaireQuestion::DEFAULT_FILE_MIMES) }}"
                   placeholder="{{ App\Models\QuestionnaireQuestion::DEFAULT_FILE_MIMES }}">
            <div class="upload-hint">File extensions, comma separated.</div>
            <div class="field-error" data-error="mimes"></div>
        </div>

        <div data-file-field hidden>
            <label class="field-label" for="q_max_kb">Largest file (KB)</label>
            <input type="number" id="q_max_kb" name="max_kb" min="64" max="20480"
                   value="{{ old('max_kb', $model?->isFile() ? $model->fileMaxKb() : App\Models\QuestionnaireQuestion::DEFAULT_FILE_MAX_KB) }}">
            <div class="upload-hint">5120 KB = 5 MB. The server caps uploads too, so keep this sensible.</div>
            <div class="field-error" data-error="max_kb"></div>
        </div>

        <div>
            <label class="field-label">Answer required</label>
            <label class="switch-row">
                <input type="hidden" name="is_required" value="0">
                <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $model?->is_required ?? true))>
                <span>People must answer this one</span>
            </label>
        </div>

        <div>
            <label class="field-label">Live</label>
            <label class="switch-row">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $model?->is_active ?? true))>
                <span>Show on the public form</span>
            </label>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Add Question' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
