<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\AchCustomer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DwollaClientTokenRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const ALLOWED_ACTIONS = [
        'customer.create',
        'customer.update',
        'customer.fundingsources.create',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('manage', AchCustomer::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(self::ALLOWED_ACTIONS)],
            '_links' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dwollaPayload(): array
    {
        $payload = $this->validated();

        unset($payload['action']);

        return array_filter([
            'action' => $this->validated('action'),
            ...$payload,
        ], static fn ($value): bool => $value !== null);
    }
}
