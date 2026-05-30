<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\Communication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SendCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Communication::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lead_id' => ['required', 'integer', 'exists:leads,id'],
            'channel' => ['required', 'string', Rule::in(['email', 'sms'])],
            'message' => ['required', 'string', 'max:5000'],
            'subject' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function leadId(): int
    {
        return (int) $this->validated('lead_id');
    }

    public function channel(): string
    {
        return (string) $this->validated('channel');
    }

    public function messageBody(): string
    {
        return (string) $this->validated('message');
    }

    public function subjectLine(): ?string
    {
        $value = $this->validated('subject');

        return $value !== null ? (string) $value : null;
    }
}
