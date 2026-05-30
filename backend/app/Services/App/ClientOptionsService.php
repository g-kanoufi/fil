<?php

declare(strict_types=1);

namespace App\Services\App;

use App\Models\ClientSetting;

final class ClientOptionsService
{
    /**
     * Branding + runtime options (legacy get_application_options subset).
     *
     * @return array<string, mixed>
     */
    public function forStaffApp(): array
    {
        $stored = ClientSetting::query()
            ->pluck('value', 'key')
            ->map(fn ($value) => is_array($value) ? $value : ['value' => $value])
            ->map(fn (array $row) => $row['value'] ?? $row)
            ->all();

        return array_merge($this->defaults(), $stored);
    }

    /**
     * Public SPA branding (login shell + CSS vars before auth).
     *
     * @return array<string, mixed>
     */
    public function publicBranding(): array
    {
        $options = $this->forStaffApp();

        return [
            'brandName' => $options['brandName'] ?? config('app.name', 'FIL'),
            'logoUrl' => filled($options['logoUrl'] ?? null) ? (string) $options['logoUrl'] : null,
            'faviconUrl' => filled($options['faviconUrl'] ?? null) ? (string) $options['faviconUrl'] : null,
            'clientBranding' => (bool) ($options['clientBranding'] ?? true),
            'highlightColor' => $options['highlightColor'] ?? null,
            'linkColor' => $options['linkColor'] ?? null,
            'topBarColor' => $options['topBarColor'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'brandName' => config('app.name', 'FIL'),
            'topBarColor' => '#53387b',
            'highlightColor' => '#53387b',
            'linkColor' => '#53387b',
            'menuColor' => '#f5f5f5',
            'textColor' => '#3c404c',
            'logoUrl' => filled(env('FIL_BRAND_LOGO_URL')) ? (string) env('FIL_BRAND_LOGO_URL') : null,
            'faviconUrl' => filled(env('FIL_FAVICON_URL')) ? (string) env('FIL_FAVICON_URL') : null,
            'timeZone' => config('app.timezone', 'UTC'),
            'enable_zai' => true,
            'clientBranding' => true,
        ];
    }
}
