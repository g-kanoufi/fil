<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use App\Services\Leads\LeadPipelineCatalog;

final class NotificationMergeTagRenderer
{
    public function __construct(
        private readonly LeadPipelineCatalog $pipelineCatalog,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function render(string $template, Lead $lead, ?User $recipient = null, array $context = []): string
    {
        $presentation = $this->pipelineCatalog->presentation($lead);
        $prospect = $lead->prospect;

        $replacements = [
            '{post/id}' => (string) $lead->id,
            '{post/post_title}' => $lead->title,
            '{post/post_type}' => 'application',
            '{postmeta/lead_status}' => (string) ($lead->lead_status ?? ''),
            '{postmeta/lead_fdd_status}' => (string) ($lead->lead_fdd_status ?? ''),
            '{postmeta/lead_temp}' => (string) ($lead->lead_temp ?? ''),
            '{postmeta/lead_source}' => (string) ($lead->lead_source ?? ''),
            '{postmeta/pipeline_phase}' => $presentation['pipeline_phase_label'],
            '{postmeta/likelihood_to_close}' => (string) ($lead->likelihood_to_close ?? ''),
            '{user/user_email}' => $recipient?->email ?? $prospect?->email ?? '',
            '{user/first_name}' => $recipient?->first_name ?? $prospect?->first_name ?? '',
            '{user/last_name}' => $recipient?->last_name ?? $prospect?->last_name ?? '',
            '{user/display_name}' => $recipient?->name ?? $prospect?->name ?? '',
            '{fil/app_url}' => rtrim((string) config('app.url'), '/').'/app',
            '{fil/lead_url}' => rtrim((string) config('app.url'), '/').'/app/reports/leads/'.$lead->id,
        ];

        if (isset($context['from_phase'], $context['to_phase'])) {
            $replacements['{fil/from_phase}'] = $this->pipelineCatalog->phaseLabel((int) $context['from_phase']);
            $replacements['{fil/to_phase}'] = $this->pipelineCatalog->phaseLabel((int) $context['to_phase']);
        }

        $output = str_replace(array_keys($replacements), array_values($replacements), $template);

        return preg_replace_callback(
            '/\{postmeta\/([a-z0-9_\-]+)\}/i',
            function (array $matches) use ($lead): string {
                $key = $matches[1];
                $formData = is_array($lead->form_data ?? null) ? $lead->form_data : [];

                return (string) ($lead->getAttribute($key) ?? $formData[$key] ?? '');
            },
            $output,
        ) ?? $output;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function renderForUser(
        string $template,
        User $subjectUser,
        ?User $recipient = null,
        array $context = [],
    ): string {
        $replacements = [
            '{user/user_email}' => $recipient?->email ?? $subjectUser->email ?? '',
            '{user/first_name}' => $recipient?->first_name ?? $subjectUser->first_name ?? '',
            '{user/last_name}' => $recipient?->last_name ?? $subjectUser->last_name ?? '',
            '{user/display_name}' => $recipient?->name ?? $subjectUser->name ?? '',
            '{fil/app_url}' => rtrim((string) config('app.url'), '/').'/app',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function renderForStore(
        string $template,
        Store $store,
        ?User $recipient = null,
        array $context = [],
    ): string {
        $inspectionDue = $context['inspection_due_at'] ?? $store->next_inspection_at?->toDateString();

        $replacements = [
            '{user/user_email}' => $recipient?->email ?? '',
            '{user/first_name}' => $recipient?->first_name ?? '',
            '{user/last_name}' => $recipient?->last_name ?? '',
            '{user/display_name}' => $recipient?->name ?? '',
            '{fil/app_url}' => rtrim((string) config('app.url'), '/').'/app',
            '{fil/store_name}' => $store->name,
            '{fil/store_url}' => rtrim((string) config('app.url'), '/').'/app/reports/stores/'.$store->id,
            '{fil/next_inspection_at}' => (string) $inspectionDue,
            '{fil/store_status}' => (string) ($store->store_status ?? ''),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
