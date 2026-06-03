<?php

declare(strict_types=1);

/**
 * Provider webhook event → communication status + activity action.
 * Active when MAILGUN_* / TWILIO_* credentials are set (see CommunicationProviderReadiness).
 */
return [
    'mailgun' => [
        'events' => [
            'delivered' => [
                'communication_status' => 'delivered',
                'activity_action' => 'delivered',
            ],
            'failed' => [
                'communication_status' => 'failed',
                'activity_action' => 'failed',
            ],
            'rejected' => [
                'communication_status' => 'failed',
                'activity_action' => 'failed',
            ],
            'complained' => [
                'communication_status' => 'complained',
                'activity_action' => 'failed',
            ],
            'opened' => [
                'communication_status' => null,
                'activity_action' => 'opened',
                'meta_key' => 'opened_at',
            ],
            'clicked' => [
                'communication_status' => null,
                'activity_action' => 'clicked',
                'meta_key' => 'clicked_at',
            ],
        ],
    ],

    'twilio' => [
        'statuses' => [
            'delivered' => [
                'communication_status' => 'delivered',
                'activity_action' => 'delivered',
            ],
            'read' => [
                'communication_status' => 'read',
                'activity_action' => 'read',
            ],
            'undelivered' => [
                'communication_status' => 'failed',
                'activity_action' => 'failed',
            ],
            'failed' => [
                'communication_status' => 'failed',
                'activity_action' => 'failed',
            ],
        ],
    ],
];
