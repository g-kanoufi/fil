<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

final class ThrottleStaffLogin
{
    public function __construct(
        private readonly ThrottleRequests $throttle,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('production', 'staging')) {
            return $next($request);
        }

        return $this->throttle->handle($request, $next, 10, 1);
    }
}
