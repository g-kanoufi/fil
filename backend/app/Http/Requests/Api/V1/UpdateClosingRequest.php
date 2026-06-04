<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Services\Closings\ClosingWorkflowCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('closing')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $catalog = app(ClosingWorkflowCatalog::class);

        return [
            'status' => ['sometimes', 'string', Rule::in(array_keys($catalog->statuses()))],
            'fee_lines' => ['sometimes', 'array'],
            'fee_lines.*.label' => ['required', 'string', 'max:120'],
            'fee_lines.*.amount_cents' => ['required', 'integer', 'min:0'],
            'document_ids' => ['sometimes', 'array'],
            'document_ids.*' => ['integer', 'exists:documents,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->has('status')) {
                return;
            }

            $closing = $this->route('closing');

            if ($closing === null) {
                return;
            }

            $next = (string) $this->input('status');
            $current = (string) $closing->status;

            if (! app(ClosingWorkflowCatalog::class)->canTransition($current, $next)) {
                $validator->errors()->add('status', 'Invalid closing status transition.');
            }
        });
    }

    /**
     * @return list<array{label: string, amount_cents: int}>
     */
    public function feeLines(): array
    {
        $lines = $this->validated('fee_lines');

        return app(ClosingWorkflowCatalog::class)->normalizeFeeLines(is_array($lines) ? $lines : null);
    }

    /**
     * @return list<int>
     */
    public function documentIds(): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->input('document_ids', []);

        return array_values(array_map(intval(...), $ids));
    }
}
