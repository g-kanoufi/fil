<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WidgetForm;
use Illuminate\Contracts\View\View;

/**
 * Staff-only preview of the public embed widget (not for client marketing sites).
 */
final class WidgetDemoController extends Controller
{
    public function inline(): View
    {
        return view('widget-demo-inline', [
            'siteKey' => $this->resolveDemoSiteKey(),
            'apiBase' => url('/'),
        ]);
    }

    public function frame(): View
    {
        return view('widget-demo-frame', [
            'siteKey' => $this->resolveDemoSiteKey(),
            'apiBase' => url('/'),
        ]);
    }

    private function resolveDemoSiteKey(): string
    {
        $fromForm = WidgetForm::query()
            ->where('status', 'active')
            ->whereNotNull('site_key')
            ->orderBy('id')
            ->value('site_key');

        if (is_string($fromForm) && $fromForm !== '') {
            return $fromForm;
        }

        /** @var list<string> $configured */
        $configured = config('fil.embed.site_keys', []);

        return $configured[0] ?? 'pk_dev';
    }
}
