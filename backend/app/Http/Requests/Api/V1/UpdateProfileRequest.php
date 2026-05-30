<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateProfileRequest extends FormRequest
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
        /** @var \App\Models\User $user */
        $user = $this->user();

        return [
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        $attributes = $this->only(['first_name', 'last_name', 'email', 'phone']);

        if ($this->filled('password')) {
            $attributes['password'] = (string) $this->input('password');
        }

        $firstName = trim((string) ($attributes['first_name'] ?? ''));
        $lastName = trim((string) ($attributes['last_name'] ?? ''));

        if ($firstName !== '' || $lastName !== '') {
            $attributes['name'] = trim("{$firstName} {$lastName}");
        }

        return $attributes;
    }
}
