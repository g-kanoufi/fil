<?php

declare(strict_types=1);

namespace App\Support\Api;

/**
 * Stable machine codes for SPA auth routing (see docs/AUTH.md).
 */
enum ApiErrorCode: string
{
    case Unauthenticated = 'unauthenticated';
    case StaffRequired = 'staff_required';
    case Forbidden = 'forbidden';
}
