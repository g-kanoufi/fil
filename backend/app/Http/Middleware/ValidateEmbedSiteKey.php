<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\WidgetForm;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ValidateEmbedSiteKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $siteKey = $request->input('site_key') ?? $request->header('X-FIL-Site-Key');

        if (! is_string($siteKey) || $siteKey === '') {
            return response()->json(['message' => 'Missing site key.'], 401);
        }

        $allowed = $this->allowedSiteKeys();

        if ($allowed === [] && app()->environment('production', 'staging')) {
            return response()->json(['message' => 'Embed intake is not configured.'], 403);
        }

        if ($allowed !== [] && ! in_array($siteKey, $allowed, true)) {
            return response()->json(['message' => 'Invalid site key.'], 403);
        }

        return $next($request);
    }

    /**
     * Union of env-configured keys and active widget-form keys in the database.
     * Each client VPS typically has one or more forms; auto-generated keys do not
     * require a separate .env edit when created in Settings → Widget form.
     *
     * @return list<string>
     */
    private function allowedSiteKeys(): array
    {
        /** @var list<string> $configured */
        $configured = config('fil.embed.site_keys', []);

        $fromForms = WidgetForm::query()
            ->where('status', 'active')
            ->whereNotNull('site_key')
            ->pluck('site_key')
            ->all();

        return array_values(array_unique(array_merge($configured, $fromForms)));
    }
}
