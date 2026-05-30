<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Lead;
use App\Services\Notifications\NotificationDispatcher;

final class LeadObserver
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function updated(Lead $lead): void
    {
        if (! $lead->wasChanged()) {
            return;
        }

        /** @var list<string> $changed */
        $changed = array_keys($lead->getChanges());
        $ignored = ['updated_at'];

        $changedFields = array_values(array_diff($changed, $ignored));

        if ($changedFields === []) {
            return;
        }

        $this->notifications->dispatch('lead.updated', $lead->fresh(), [
            'changed_fields' => $changedFields,
        ]);

        if (in_array('lead_fdd_status', $changedFields, true)) {
            $this->notifications->dispatch('lead.fdd_status_changed', $lead->fresh(), [
                'changed_fields' => ['lead_fdd_status'],
            ]);
        }

        if (in_array('lead_status', $changedFields, true)) {
            $this->notifications->dispatch('lead.status_changed', $lead->fresh(), [
                'changed_fields' => ['lead_status'],
            ]);
        }

        if (in_array('form_data', $changedFields, true)) {
            $this->notifications->dispatch('application.long_form_updated', $lead->fresh(), [
                'changed_fields' => ['form_data'],
            ]);
        }
    }
}
