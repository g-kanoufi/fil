<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LogoutUser
{
    public function handle(Request $request): void
    {
        Auth::guard('web')->logout();

        if (! $request->hasSession()) {
            return;
        }

        /** @var Session $session */
        $session = $request->session();
        $session->invalidate();
        $session->regenerateToken();
    }
}
