<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Services\Operations\EntityNoteSubjectResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateStaffTodoRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'assignee_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'completed' => ['sometimes', 'boolean'],
            'subject_type' => ['sometimes', 'nullable', 'string', Rule::in(app(EntityNoteSubjectResolver::class)->supportedTypes())],
            'subject_id' => ['sometimes', 'nullable', 'integer', 'required_with:subject_type'],
        ];
    }
}
