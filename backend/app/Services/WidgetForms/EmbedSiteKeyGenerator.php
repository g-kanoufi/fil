<?php

declare(strict_types=1);

namespace App\Services\WidgetForms;

use App\Models\WidgetForm;
use Illuminate\Support\Str;

/**
 * Generates opaque publishable site keys for embed intake (SEC-011).
 *
 * Keys are safe to embed in client HTML (like a Stripe publishable key). Intake
 * security comes from allowlisting + reCAPTCHA + rate limits — not key secrecy.
 */
final class EmbedSiteKeyGenerator
{
    public function generate(): string
    {
        do {
            $key = 'pk_live_'.Str::lower(Str::random(24));
        } while (WidgetForm::query()->where('site_key', $key)->exists());

        return $key;
    }
}
