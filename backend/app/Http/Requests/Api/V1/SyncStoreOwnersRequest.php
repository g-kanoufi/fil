<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class SyncStoreOwnersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'owners' => ['required', 'array'],
            'owners.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'owners.*.ownership_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'owners.*.role' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return list<array{user_id: int, ownership_pct: float|null, role: string|null}>
     */
    public function owners(): array
    {
        /** @var list<array{user_id: int, ownership_pct?: float|null, role?: string|null}> $owners */
        $owners = $this->validated('owners');

        return array_map(static fn (array $owner): array => [
            'user_id' => (int) $owner['user_id'],
            'ownership_pct' => isset($owner['ownership_pct']) ? (float) $owner['ownership_pct'] : null,
            'role' => isset($owner['role']) ? (string) $owner['role'] : null,
        ], $owners);
    }
}
