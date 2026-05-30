<?php

declare(strict_types=1);

namespace App\Support\Mail;

final class MailBranding
{
    /**
     * @return array{
     *     logo_url: ?string,
     *     logo_width: string,
     *     background_color: string,
     *     brand_color: string,
     *     button_color: string,
     *     footer_text: string,
     *     app_url: string,
     *     app_name: string,
     * }
     */
    public static function get(): array
    {
        $branding = config('fil-notifications.branding', []);

        return [
            'logo_url' => filled($branding['logo_url'] ?? null) ? (string) $branding['logo_url'] : null,
            'logo_width' => (string) ($branding['logo_width'] ?? '300'),
            'background_color' => (string) ($branding['background_color'] ?? '#f5f5f5'),
            'brand_color' => (string) ($branding['brand_color'] ?? '#53387B'),
            'button_color' => (string) ($branding['button_color'] ?? '#999999'),
            'footer_text' => (string) ($branding['footer_text'] ?? config('app.name', 'FIL')),
            'app_url' => (string) ($branding['app_url'] ?? config('app.url', '')),
            'app_name' => (string) config('app.name', 'FIL'),
        ];
    }

    /**
     * Wrap plain text or partial HTML in the branded layout view data.
     */
    public static function prepareBodyHtml(string $body): string
    {
        $trimmed = trim($body);
        if ($trimmed === '') {
            return '';
        }

        if ($trimmed !== strip_tags($trimmed)) {
            return $trimmed;
        }

        $paragraphs = preg_split("/\r\n|\r|\n/", $trimmed) ?: [];

        return collect($paragraphs)
            ->map(static fn (string $line): string => trim($line))
            ->filter()
            ->map(static fn (string $line): string => '<p>'.e($line).'</p>')
            ->join('');
    }

    public static function plainTextFromHtml(string $html): string
    {
        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }
}
