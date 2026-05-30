<?php

use App\Services\Notifications\NotificationRecipientTokenNormalizer;

beforeEach(function () {
    $this->normalizer = new NotificationRecipientTokenNormalizer;
});

test('normalizes string tokens', function () {
    expect($this->normalizer->normalizeList(['related:prospect', 'admin@example.com']))->toBe(['related:prospect', 'admin@example.com']);
});

test('normalizes wp notification repeater objects', function () {
    expect($this->normalizer->normalizeList([
        ['type' => 'email', 'recipient' => 'related:prospect'],
    ]))->toBe(['related:prospect']);

    expect($this->normalizer->normalizeList([
        ['type' => 'email', 'recipient' => 'lead_owner'],
    ]))->toBe(['related:lead_owner']);

    expect($this->normalizer->normalizeList([
        ['type' => 'role', 'recipient' => 'administrator'],
    ]))->toBe(['role:administrator']);
});
