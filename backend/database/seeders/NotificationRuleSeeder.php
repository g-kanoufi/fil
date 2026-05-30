<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\NotificationRule;
use Illuminate\Database\Seeder;

final class NotificationRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'hash' => 'fil-lead-created-prospect',
                'title' => 'New lead — welcome email',
                'trigger_slug' => 'lead.created',
                'enabled' => true,
                'subject' => 'Thanks for your interest, {user/first_name}',
                'body_html' => '<p>Hi {user/first_name},</p><p>We received your franchise application and will be in touch soon.</p><p><a href="{fil/lead_url}">View your application</a></p>',
                'recipients' => ['related:prospect'],
                'profile_roles' => ['prospect'],
            ],
            [
                'hash' => 'fil-fdd-sent-prospect',
                'title' => 'FDD sent to prospect',
                'trigger_slug' => 'application.send_prospect_fdd',
                'enabled' => true,
                'subject' => 'Your Franchise Disclosure Document',
                'body_html' => '<p>Hi {user/first_name},</p><p>Your FDD for {post/post_title} is ready for review.</p>',
                'recipients' => ['related:prospect'],
                'profile_roles' => ['prospect'],
            ],
            [
                'hash' => 'fil-phase-changed-owner',
                'title' => 'Lead pipeline update (owner)',
                'trigger_slug' => 'lead.phase_changed',
                'enabled' => true,
                'subject' => 'Pipeline update: {post/post_title}',
                'body_html' => '<p>{post/post_title} moved to {fil/to_phase}.</p>',
                'recipients' => ['related:lead_owner'],
                'profile_roles' => ['lead_owner', 'franchisor'],
            ],
        ];

        foreach ($rules as $rule) {
            NotificationRule::query()->updateOrCreate(
                ['hash' => $rule['hash']],
                $rule,
            );
        }
    }
}
