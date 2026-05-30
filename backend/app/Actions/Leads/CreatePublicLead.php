<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\User;
use App\Services\Drips\DripEnrollmentService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreatePublicLead
{
    public function __construct(
        private readonly CreateLead $createLead,
        private readonly DripEnrollmentService $drips,
    ) {}

    /**
     * @param  array{email: string, first_name: string, last_name: string, phone?: string|null}  $payload
     */
    public function handle(array $payload): Lead
    {
        $prospect = User::query()->firstOrCreate(
            ['email' => $payload['email']],
            [
                'name' => trim("{$payload['first_name']} {$payload['last_name']}"),
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'],
                'phone' => $payload['phone'] ?? null,
                'password' => Hash::make(Str::random(32)),
            ],
        );

        if (! $prospect->hasRole('prospect')) {
            $prospect->assignRole('prospect');
        }

        $title = trim("{$payload['first_name']} {$payload['last_name']}").' Application';

        $lead = $this->createLead->handle([
            'title' => $title,
            'prospect_user_id' => $prospect->id,
            'lead_source' => 'widget',
            'pipeline_phase' => 1,
            'eligible_for_drip' => true,
        ]);

        $this->drips->enroll($lead, 'lead_created');

        app(NotificationDispatcher::class)
            ->dispatch('lead.created', $lead->fresh());

        return $lead->fresh(['prospect']);
    }
}
