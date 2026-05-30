<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Public\V1;

use App\Models\Field;
use App\Models\WidgetForm;
use App\Services\Embed\RecaptchaVerifier;
use App\Services\WidgetForms\WidgetFormConfigService;
use App\Support\Fields\FieldTypes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreLeadRequest extends FormRequest
{
    /**
     * Core lead intake keys handled explicitly (not treated as custom fields).
     *
     * @var list<string>
     */
    private const CORE_KEYS = ['site_key', 'email', 'first_name', 'last_name', 'phone', 'g-recaptcha-response'];

    private ?WidgetForm $resolvedForm = null;

    private bool $formResolved = false;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('site_key')) {
            return;
        }

        $headerKey = $this->header('X-FIL-Site-Key');

        if (is_string($headerKey) && $headerKey !== '') {
            $this->merge(['site_key' => $headerKey]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'site_key' => ['required', 'string'],
            'email' => ['required', 'email'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['prohibited'],
        ];

        if (app(RecaptchaVerifier::class)->enabled()) {
            $rules['g-recaptcha-response'] = ['required', 'string'];
        }

        foreach ($this->customFields() as $field) {
            $rules["custom.{$field->key}"] = $this->rulesForField($field);
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $verifier = app(RecaptchaVerifier::class);

            if (! app()->environment('production', 'staging') && ! $verifier->enabled()) {
                return;
            }

            if (! $verifier->verify($this->input('g-recaptcha-response'), $this->ip())) {
                $validator->errors()->add('g-recaptcha-response', 'reCAPTCHA verification failed.');
            }
        });
    }

    /**
     * Validated custom field values keyed by field key.
     *
     * @return array<string, mixed>
     */
    public function customValues(): array
    {
        /** @var array<string, mixed> $custom */
        $custom = $this->validated('custom', []);

        return $custom;
    }

    /**
     * The active widget form's custom fields (excluding core intake keys).
     *
     * @return \Illuminate\Support\Collection<int, Field>
     */
    private function customFields(): \Illuminate\Support\Collection
    {
        $form = $this->resolveForm();

        if ($form === null) {
            return collect();
        }

        return $form->formFields
            ->map(fn ($formField) => $formField->field)
            ->filter(fn ($field): bool => $field instanceof Field && ! in_array($field->key, self::CORE_KEYS, true))
            ->values();
    }

    private function resolveForm(): ?WidgetForm
    {
        if ($this->formResolved) {
            return $this->resolvedForm;
        }

        $this->formResolved = true;
        $siteKey = $this->input('site_key');
        $this->resolvedForm = app(WidgetFormConfigService::class)
            ->resolveForSiteKey(is_string($siteKey) ? $siteKey : null);

        return $this->resolvedForm;
    }

    /**
     * @return list<string>
     */
    private function rulesForField(Field $field): array
    {
        $required = (bool) $field->required;
        $base = [$required ? 'required' : 'nullable'];

        return match ($field->type) {
            FieldTypes::NUMBER, FieldTypes::RANGE, FieldTypes::RELATION_ONE => [...$base, 'numeric'],
            FieldTypes::TRUE_FALSE => [...$base, 'boolean'],
            FieldTypes::DATE, FieldTypes::DATE_TIME => [...$base, 'date'],
            FieldTypes::MULTISELECT, FieldTypes::RELATION_MANY => [...$base, 'array'],
            FieldTypes::EMAIL => [...$base, 'email'],
            FieldTypes::URL => [...$base, 'url'],
            default => [...$base, 'string'],
        };
    }
}
