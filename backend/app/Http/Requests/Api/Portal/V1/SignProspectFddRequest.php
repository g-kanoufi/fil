<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Portal\V1;

use Illuminate\Foundation\Http\FormRequest;

final class SignProspectFddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'signed_name' => ['required', 'string', 'max:255'],
            'agree' => ['accepted'],
            'vendor_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function signedName(): string
    {
        return trim($this->string('signed_name')->toString());
    }

    public function vendorReference(): ?string
    {
        $value = $this->validated('vendor_reference');

        return is_string($value) && $value !== '' ? $value : null;
    }
}
