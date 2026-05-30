<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreFddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Fdd::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('slug') && $this->filled('title')) {
            $this->merge([
                'slug' => Str::slug((string) $this->input('title')),
            ]);
        }

        if ($this->has('area_id') && $this->input('area_id') === '') {
            $this->merge(['area_id' => null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['unit', 'area'])],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('fdds', 'slug')],
            'area_id' => [
                Rule::requiredIf(fn () => $this->input('type') === 'area'),
                'nullable',
                'integer',
                Rule::exists('areas', 'id'),
            ],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ];
    }
}
