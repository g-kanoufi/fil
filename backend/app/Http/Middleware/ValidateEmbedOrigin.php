<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Embed\EmbedOriginGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ValidateEmbedOrigin
{
    public function __construct(private readonly EmbedOriginGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $allowed */
        $allowed = config('fil.embed_allowed_origins', []);

        if (! $this->guard->allows($request, $allowed)) {
            return response()->json(['message' => $this->guard->rejectionMessage($allowed)], 403);
        }

        return $next($request);
    }
}
