<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Public\V1;

use App\Models\Lead;
use App\Services\Portal\ProspectPortalConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lead */
final class PublicLeadResource extends JsonResource
{
    public function __construct(
        Lead $resource,
        private readonly ?string $portalSetupToken = null,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $portal = app(ProspectPortalConfig::class);
        $payload = [
            'message' => 'Thank you for your application.',
            'title' => $this->title,
        ];

        $redirect = $portal->intakeRedirectPayload($this->portalSetupToken);

        if ($redirect !== null) {
            $payload['portal'] = $redirect;
        }

        return $payload;
    }
}
