<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateContact', $this->route('contact')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'custom' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function customFieldValues(): array
    {
        $custom = $this->validated('custom');

        return is_array($custom) ? $custom : [];
    }
}
