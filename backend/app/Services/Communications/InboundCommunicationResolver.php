<?php

declare(strict_types=1);

namespace App\Services\Communications;

use App\Models\Lead;
use App\Models\User;

final readonly class InboundCommunicationMatch
{
    public function __construct(
        public ?int $leadId,
        public ?int $contactUserId,
        public ?string $senderName,
    ) {}
}

final class InboundCommunicationResolver
{
    public function __construct(
        private readonly CommunicationSuppressionService $suppressions,
    ) {}

    public function resolveByEmail(string $address): InboundCommunicationMatch
    {
        $email = $this->suppressions->normalizeEmail($this->extractEmailAddress($address));

        if ($email === '') {
            return new InboundCommunicationMatch(null, null, $this->displayName($address));
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user === null) {
            return new InboundCommunicationMatch(null, null, $this->displayName($address));
        }

        return $this->matchForUser($user, $this->displayName($address, $user->name));
    }

    public function resolveByPhone(string $phone): InboundCommunicationMatch
    {
        $normalized = $this->suppressions->normalizePhone($phone);

        if ($normalized === '') {
            return new InboundCommunicationMatch(null, null, $phone !== '' ? $phone : null);
        }

        $user = User::query()
            ->whereNotNull('phone')
            ->get(['id', 'name', 'phone'])
            ->first(
                fn (User $candidate): bool => $this->suppressions->normalizePhone((string) $candidate->phone) === $normalized,
            );

        if ($user === null) {
            return new InboundCommunicationMatch(null, null, $phone);
        }

        return $this->matchForUser($user, $user->name);
    }

    private function matchForUser(User $user, ?string $senderName): InboundCommunicationMatch
    {
        $leadId = Lead::query()
            ->where('prospect_user_id', $user->id)
            ->orderByDesc('updated_at')
            ->value('id');

        return new InboundCommunicationMatch(
            is_numeric($leadId) ? (int) $leadId : null,
            $user->id,
            $senderName,
        );
    }

    public function extractEmailAddress(string $value): string
    {
        if (preg_match('/<([^>]+)>/', $value, $matches) === 1) {
            return $matches[1];
        }

        return $value;
    }

    private function displayName(string $raw, ?string $fallback = null): ?string
    {
        if ($fallback !== null && $fallback !== '') {
            return $fallback;
        }

        if (preg_match('/^([^<]+)</', $raw, $matches) === 1) {
            $name = trim($matches[1], " \t\"'");

            return $name !== '' ? $name : null;
        }

        return str_contains($raw, '@') ? null : ($raw !== '' ? $raw : null);
    }
}
