<?php

declare(strict_types=1);
use App\Models\Lead;
use App\Models\User;
use App\Services\Communications\InboundCommunicationResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('resolves lead by prospect email', function () {
    $prospect = User::factory()->create(['email' => 'prospect@example.com']);
    $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);

    $match = app(InboundCommunicationResolver::class)->resolveByEmail('prospect@example.com');

    expect($match->leadId)->toBe($lead->id);
    expect($match->contactUserId)->toBe($prospect->id);
});
test('resolves lead by prospect phone suffix', function () {
    $prospect = User::factory()->create(['phone' => '+1 (555) 123-4567']);
    $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);

    $match = app(InboundCommunicationResolver::class)->resolveByPhone('+15551234567');

    expect($match->leadId)->toBe($lead->id);
    expect($match->contactUserId)->toBe($prospect->id);
});
test('extracts email from rfc address', function () {
    $resolver = app(InboundCommunicationResolver::class);

    expect($resolver->extractEmailAddress('User Name <user@example.com>'))->toBe('user@example.com');
});
