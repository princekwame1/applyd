@php($isEdit = isset($model) && $model)
<form method="POST"
      action="{{ $isEdit ? route('dashboard.surveys.questions.update', $model) : route('dashboard.surveys.questions.store') }}"
      data-modal-form autocomplete="off">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="modal-grid">
        <div>
            <label class="field-label" for="q_survey_type">Survey <span class="req">*</span></label>
            @if ($isEdit)
                <input type="text" id="q_survey_type" value="{{ App\Support\Surveys::label($model->survey_type) }}" disabled>
                <div class="upload-hint">A question can't be moved between surveys — add it to the other one instead.</div>
            @else
                <select id="q_survey_type" name="survey_type" required>
                    @foreach (App\Support\Surveys::types() as $type => $copy)
                        <option value="{{ $type }}" @selected(old('survey_type') === $type)>{{ $copy['label'] }}</option>
                    @endforeach
                </select>
            @endif
            <div class="field-error" data-error="survey_type"></div>
        </div>

        <div>
            <label class="field-label" for="q_type">Answer type <span class="req">*</span></label>
            <select id="q_type" name="type" required data-question-type>
                @foreach (App\Models\SurveyQuestion::TYPES as $type)
                    <option value="{{ $type }}" @selected(old('type', $model?->type) === $type)>
                        {{ ['choice' => 'Choice — pick one option', 'scale' => 'Scale — rate 1 to N', 'text' => 'Text — free typing'][$type] }}
                    </option>
                @endforeach
            </select>
            <div class="field-error" data-error="type"></div>
        </div>

        <div class="span-2">
            <label class="field-label" for="q_prompt">Question <span class="req">*</span></label>
            <input type="text" id="q_prompt" name="prompt" value="{{ old('prompt', $model?->prompt) }}"
                   placeholder="e.g. What made you sign up for this bootcamp?" required>
            <div class="field-error" data-error="prompt"></div>
        </div>

        @unless ($isEdit)
            <div>
                <label class="field-label" for="q_key">Key <span class="req">*</span></label>
                <input type="text" id="q_key" name="key" value="{{ old('key') }}" placeholder="e.g. motivation" required>
                <div class="upload-hint">Lower-case, no spaces. Answers are stored under this key, so it can't be changed later.</div>
                <div class="field-error" data-error="key"></div>
            </div>
        @endunless

        <div>
            <label class="field-label" for="q_sort_order">Order</label>
            <input type="number" id="q_sort_order" name="sort_order" min="0"
                   value="{{ old('sort_order', $model?->sort_order) }}" placeholder="Leave empty to add at the end">
            <div class="field-error" data-error="sort_order"></div>
        </div>

        <div class="span-2" data-options-field>
            <label class="field-label" for="q_options">Options <span class="req">*</span></label>
            <textarea id="q_options" name="options" rows="5" style="width:100%; font-family:inherit;"
                      placeholder="One per line.&#10;Career growth&#10;Plain curiosity&#10;Required for work">{{ old('options', $model ? implode("\n", $model->optionList()) : '') }}</textarea>
            <div class="upload-hint" data-options-hint>One per line. For a scale these are the labels for 1, 2, 3… in order.</div>
            <div class="field-error" data-error="options"></div>
        </div>

        <div class="span-2" data-placeholder-field>
            <label class="field-label" for="q_placeholder">Placeholder</label>
            <input type="text" id="q_placeholder" name="placeholder" value="{{ old('placeholder', $model?->placeholder) }}"
                   placeholder="Type your answer…">
            <div class="field-error" data-error="placeholder"></div>
        </div>

        <div>
            <label class="field-label">Answer required</label>
            <label class="switch-row">
                <input type="hidden" name="required" value="0">
                <input type="checkbox" name="required" value="1" @checked(old('required', $model?->required ?? true))>
                <span>Participants must answer this one</span>
            </label>
        </div>

        <div>
            <label class="field-label">Live</label>
            <label class="switch-row">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" @checked(old('active', $model?->active ?? true))>
                <span>Show on the public form</span>
            </label>
        </div>
    </div>

    <div class="modal-actions">
        <button type="submit" class="btn btn-brand btn-sm">{{ $isEdit ? 'Save Changes' : 'Add Question' }}</button>
        <button type="button" class="btn btn-sm btn-outline" data-modal-close>Cancel</button>
    </div>
</form>
