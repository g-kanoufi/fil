<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Fdd;
use App\Rules\PdfFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateFddRequest extends FormRequest
{
    public function authorize(): bool
    {
        $fdd = $this->route('fdd');

        return $fdd instanceof Fdd
            && ($this->user()?->can('update', $fdd) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('area_id') && $this->input('area_id') === '') {
            $this->merge(['area_id' => null]);
        }

        if ($this->filled('title') && ! $this->filled('slug')) {
            $this->merge([
                'slug' => Str::slug((string) $this->input('title')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Fdd $fdd */
        $fdd = $this->route('fdd');

        return [
            'type' => ['sometimes', Rule::in(['unit', 'area'])],
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('fdds', 'slug')->ignore($fdd->id),
            ],
            'area_id' => [
                Rule::requiredIf(fn () => ($this->input('type') ?? $fdd->type) === 'area'),
                'nullable',
                'integer',
                Rule::exists('areas', 'id'),
            ],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'pdf' => ['sometimes', 'file', 'mimes:pdf', 'max:51200', new PdfFile],
        ];
    }
}
