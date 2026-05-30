<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\RoyaltyPeriod;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

final class CalculateRoyaltyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('calculate', RoyaltyPeriod::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gross_revenue' => ['required', 'numeric', 'min:0'],
            'order_count' => ['sometimes', 'integer', 'min:0'],
            'royalty_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
        ];
    }

    public function store(): Store
    {
        /** @var Store $store */
        $store = $this->route('store');

        return $store;
    }
}
