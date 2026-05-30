<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Services\Auth\FieldAccessService;
use App\Services\Auth\NavigationService;
use App\Services\Auth\UiAccessService;
use App\Services\Auth\UserAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
final class SessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var UserAccessService $access */
        $access = app(UserAccessService::class);
        /** @var NavigationService $navigation */
        $navigation = app(NavigationService::class);
        /** @var UiAccessService $uiAccess */
        $uiAccess = app(UiAccessService::class);
        /** @var FieldAccessService $fieldAccess */
        $fieldAccess = app(FieldAccessService::class);
        $restrictions = $uiAccess->restrictionsFor($this->resource);
        $fields = $fieldAccess->forUser($this->resource);

        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'name' => trim("{$this->first_name} {$this->last_name}") ?: $this->name,
            'roles' => $this->getRoleNames()->values()->all(),
            'primary_role' => $access->primaryRole($this->resource),
            'permissions' => $access->permissions($this->resource),
            'navigation' => $navigation->forUser($this->resource),
            'ui_restrictions' => $restrictions,
            'authorized_notes' => $uiAccess->notesAccessFor($this->resource),
            'hidden_field_keys' => $fields['hidden_field_keys'],
            'readonly_field_keys' => $fields['readonly_field_keys'],
            'user_has_panel_access' => $restrictions['features']['detail_panel'] ?? true,
        ];
    }
}
