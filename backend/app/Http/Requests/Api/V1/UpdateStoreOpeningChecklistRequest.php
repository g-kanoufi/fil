<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateStoreOpeningChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('store')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['sometimes', 'array'],
            'items.*.key' => ['required', 'string', 'max:80'],
            'items.*.completed' => ['sometimes', 'boolean'],
            'items.*.completed_at' => ['sometimes', 'nullable', 'date'],
            'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'buildout_started_at' => ['sometimes', 'nullable', 'date'],
            'expected_opening_at' => ['sometimes', 'nullable', 'date'],
            'opened_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * @return list<array{key: string, completed?: bool|null, completed_at?: string|null, notes?: string|null}>
     */
    public function items(): array
    {
        /** @var list<array{key: string, completed?: bool|null, completed_at?: string|null, notes?: string|null}> */
        return $this->input('items', []);
    }

    public function hasTimeline(): bool
    {
        return $this->hasAny(['buildout_started_at', 'expected_opening_at', 'opened_at']);
    }

    /**
     * @return array<string, string|null>
     */
    public function timelineAttributes(): array
    {
        $attributes = [];

        foreach (['buildout_started_at', 'expected_opening_at', 'opened_at'] as $key) {
            if ($this->has($key)) {
                $attributes[$key] = $this->input($key);
            }
        }

        return $attributes;
    }
}
