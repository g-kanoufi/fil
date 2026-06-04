<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Api\ApiProblem;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class EnsureProspectPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiProblem::unauthenticated();
        }

        if (! Gate::forUser($user)->allows('accessProspectPortal')) {
            return ApiProblem::forbidden('This account cannot access the prospect portal.');
        }

        return $next($request);
    }
}
