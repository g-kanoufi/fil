<?php

declare(strict_types=1);

namespace App\Services\Portal;

final class ProspectPortalConfig
{
    /**
     * @return array{portal_app_url: string, redirect_after_intake: bool}
     */
    public function widgetMeta(): array
    {
        return [
            'portal_app_url' => $this->appUrl(),
            'redirect_after_intake' => $this->redirectAfterIntake(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function intakeRedirectPayload(?string $setupToken): ?array
    {
        if ($setupToken === null || ! $this->enabled()) {
            return null;
        }

        $setupPath = (string) config('fil-platform.portal.redirect_path', '/portal/setup');
        $redirectPath = $setupPath.'?token='.urlencode($setupToken);

        return [
            'setup_token' => $setupToken,
            'setup_path' => $setupPath,
            'redirect_url' => $this->appUrl().$redirectPath,
            'redirect_after_intake' => $this->redirectAfterIntake(),
        ];
    }

    public function enabled(): bool
    {
        return (bool) config('fil-platform.portal.enabled', true);
    }

    private function appUrl(): string
    {
        return rtrim((string) config('fil-platform.portal.app_url'), '/');
    }

    private function redirectAfterIntake(): bool
    {
        return (bool) config('fil-platform.portal.redirect_after_intake', true);
    }
}
