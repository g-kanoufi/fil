<?php

declare(strict_types=1);

namespace App\Services\WidgetForms;

use App\Models\WidgetForm;
use App\Models\WidgetFormField;
use Illuminate\Support\Collection;

final class WidgetFormConfigService
{
    /**
     * Resolve the active widget form for a site key — exact match first, then the
     * default (null site_key) form.
     */
    public function resolveForSiteKey(?string $siteKey): ?WidgetForm
    {
        return WidgetForm::query()
            ->where('status', 'active')
            ->when(
                is_string($siteKey) && $siteKey !== '',
                fn ($query) => $query->where(fn ($inner) => $inner
                    ->where('site_key', $siteKey)
                    ->orWhereNull('site_key')),
                fn ($query) => $query->whereNull('site_key'),
            )
            ->with(['formFields' => fn ($query) => $query->where('status', 'active')->orderBy('sort_order'), 'formFields.field'])
            ->orderByRaw('site_key IS NULL')
            ->first();
    }

    /**
     * Shape the form into the public descriptor consumed by the embed widget.
     *
     * @return array{form_key: string, version: int, fields: list<array<string, mixed>>, theme: array<string, string>|null}
     */
    public function publicDescriptor(?WidgetForm $form): array
    {
        if ($form === null) {
            return ['form_key' => 'lead_short', 'version' => 1, 'fields' => [], 'theme' => null];
        }

        /** @var Collection<int, WidgetFormField> $formFields */
        $formFields = $form->formFields;

        $fields = $formFields
            ->filter(fn (WidgetFormField $formField): bool => $formField->field !== null)
            ->map(function (WidgetFormField $formField): array {
                $field = $formField->field;
                /** @var array<string, mixed> $config */
                $config = $field->config ?? [];

                return [
                    'key' => $field->key,
                    'name' => $field->key,
                    'label' => $formField->label_override ?? $field->name,
                    'type' => $field->type,
                    'required' => $formField->required_override ?? (bool) $field->required,
                    'placeholder' => $formField->placeholder,
                    'width' => $formField->width ?? 'full',
                    'options' => $config['choices'] ?? null,
                ];
            })
            ->values()
            ->all();

        return [
            'form_key' => $form->key,
            'version' => $form->version,
            'fields' => $fields,
            'theme' => $this->publicTheme($form),
        ];
    }

    /**
     * Optional embed styling from widget form settings (widget_forms.settings.theme).
     *
     * @return array<string, string>|null
     */
    public function publicTheme(?WidgetForm $form): ?array
    {
        if ($form === null) {
            return null;
        }

        /** @var array<string, mixed> $settings */
        $settings = $form->settings ?? [];
        $theme = $settings['theme'] ?? null;

        if (! is_array($theme) || $theme === []) {
            return null;
        }

        $normalized = [];

        foreach ($theme as $key => $value) {
            if (is_string($key) && is_string($value) && $value !== '') {
                $normalized[$key] = $value;
            }
        }

        return $normalized === [] ? null : $normalized;
    }
}
