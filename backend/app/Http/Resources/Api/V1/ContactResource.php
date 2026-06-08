<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Services\Fields\EntityFieldValueReader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class ContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()->all()),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'custom' => $this->when(
                $request->routeIs('api.v1.contacts.show', 'api.v1.contacts.update'),
                fn (): array => app(EntityFieldValueReader::class)->forEntity('contact', $this->id, 'user'),
            ),
        ];
    }
}
