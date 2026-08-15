<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

class QuestionnaireQuestion extends Model
{
    /** Every field an admin can put on a form, in the order the picker lists them. */
    public const TYPES = [
        'short_text' => 'Short answer — a single line of text',
        'long_text' => 'Paragraph — a multi-line text box',
        'radio' => 'Multiple choice — pick one (radio buttons)',
        'checkbox' => 'Checkboxes — pick one or more',
        'select' => 'Dropdown — pick one from a list',
        'number' => 'Number',
        'email' => 'Email address',
        'phone' => 'Phone number',
        'date' => 'Date',
        'file' => 'File upload',
    ];

    /** Types whose answers come from a fixed list the admin types in. */
    public const OPTION_TYPES = ['radio', 'checkbox', 'select'];

    /** Types that accept more than one answer. */
    public const MULTI_TYPES = ['checkbox'];

    public const DEFAULT_FILE_MIMES = 'pdf,doc,docx,jpg,jpeg,png';

    public const DEFAULT_FILE_MAX_KB = 5120;

    protected $fillable = [
        'questionnaire_id',
        'key',
        'type',
        'label',
        'help_text',
        'placeholder',
        'options',
        'settings',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'settings' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function typeLabel(): string
    {
        return static::TYPES[$this->type] ?? $this->type;
    }

    /** Short label for tables and column headings. */
    public function typeName(): string
    {
        return trim(explode('—', $this->typeLabel())[0]);
    }

    public function hasOptions(): bool
    {
        return in_array($this->type, static::OPTION_TYPES, true);
    }

    public function isMultiple(): bool
    {
        return in_array($this->type, static::MULTI_TYPES, true);
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    /** Options, always an array even when the column is null. */
    public function optionList(): array
    {
        return array_values(array_filter((array) ($this->options ?? []), fn ($o) => $o !== null && $o !== ''));
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return ($this->settings ?? [])[$key] ?? $default;
    }

    public function maxSelect(): ?int
    {
        $max = (int) $this->setting('max_select', 0);

        return $max > 0 ? $max : null;
    }

    public function fileMimes(): string
    {
        return (string) $this->setting('mimes', static::DEFAULT_FILE_MIMES);
    }

    public function fileMaxKb(): int
    {
        return (int) $this->setting('max_kb', static::DEFAULT_FILE_MAX_KB);
    }

    /**
     * Uploads travel in their own input array so the validated `answers` bag
     * never mixes plain values with UploadedFile objects.
     */
    public function inputGroup(): string
    {
        return $this->isFile() ? 'uploads' : 'answers';
    }

    public function inputName(): string
    {
        return $this->inputGroup().'['.$this->key.']'.($this->isMultiple() ? '[]' : '');
    }

    /** Dotted path of this question's slot in the request, for rules and old(). */
    public function inputPath(): string
    {
        return $this->inputGroup().'.'.$this->key;
    }

    /**
     * Validation rules for this question's slot, keyed by dotted path so the
     * caller can merge them straight into one validate() call. Built from the
     * stored row, so the form can never accept an option that isn't live.
     */
    public function validationRules(): array
    {
        $presence = $this->is_required ? 'required' : 'nullable';
        $path = $this->inputPath();

        return match ($this->type) {
            'checkbox' => [
                $path => array_values(array_filter([
                    $presence,
                    'array',
                    $this->is_required ? 'min:1' : null,
                    $this->maxSelect() ? 'max:'.$this->maxSelect() : null,
                ])),
                $path.'.*' => ['string', Rule::in($this->optionList())],
            ],
            'radio', 'select' => [$path => [$presence, 'string', Rule::in($this->optionList())]],
            'long_text' => [$path => [$presence, 'string', 'max:5000']],
            'number' => [$path => [$presence, 'numeric']],
            'email' => [$path => [$presence, 'email', 'max:255']],
            'phone' => [$path => [$presence, 'string', 'max:40']],
            'date' => [$path => [$presence, 'date']],
            'file' => [$path => [$presence, 'file', 'mimes:'.$this->fileMimes(), 'max:'.$this->fileMaxKb()]],
            default => [$path => [$presence, 'string', 'max:255']],
        };
    }

    /** How a stored answer reads on the dashboard and in an export. */
    public function formatAnswer(mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '';
        }

        if (is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }

        return (string) $value;
    }
}
