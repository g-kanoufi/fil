<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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

        /** @var list<string> $allowed */
        $allowed = config('fil.embed.site_keys', []);

        if ($allowed === [] && app()->environment('production', 'staging')) {
            return response()->json(['message' => 'Embed intake is not configured.'], 403);
        }

        if ($allowed !== [] && ! in_array($siteKey, $allowed, true)) {
            return response()->json(['message' => 'Invalid site key.'], 403);
        }

        return $next($request);
    }
}
