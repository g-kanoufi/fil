<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\StaffTodo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StaffTodo */
final class StaffTodoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'due_at' => $this->due_at?->toDateString(),
            'completed' => $this->completed_at !== null,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'assignee' => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ] : null,
            'author' => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
            ],
            'subject' => $this->subject_type ? [
                'type' => $this->subject_type,
                'id' => $this->subject_id,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
