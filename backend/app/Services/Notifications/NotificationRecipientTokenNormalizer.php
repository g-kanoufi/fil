<?php

declare(strict_types=1);

namespace App\Services\Notifications;

final class NotificationRecipientTokenNormalizer
{
    /**
     * @param  list<mixed>  $entries
     * @return list<string>
     */
    public function normalizeList(array $entries): array
    {
        $tokens = [];

        foreach ($entries as $entry) {
            $token = $this->normalizeOne($entry);

            if ($token !== null && $token !== '') {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }

    public function normalizeOne(mixed $entry): ?string
    {
        if (is_string($entry) || is_numeric($entry)) {
            $token = trim((string) $entry);

            return $token !== '' ? $token : null;
        }

        if (! is_array($entry)) {
            return null;
        }

        if (isset($entry['type'])) {
            return $this->normalizeRepeaterEntry($entry);
        }

        if (isset($entry['email']) && is_string($entry['email'])) {
            $email = trim($entry['email']);

            return $email !== '' ? $email : null;
        }

        if (isset($entry['role']) && is_string($entry['role'])) {
            $role = trim($entry['role']);

            return $role !== '' ? 'related:'.$role : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function normalizeRepeaterEntry(array $entry): ?string
    {
        $type = strtolower(trim((string) ($entry['type'] ?? '')));
        $value = $entry['recipient'] ?? $entry['value'] ?? null;

        if (is_array($value)) {
            return $this->normalizeOne($value);
        }

        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'related:') || filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $value;
        }

        if ($type === 'role') {
            return 'role:'.$value;
        }

        if ($type === 'email' && ! str_contains($value, '@')) {
            return 'related:'.$value;
        }

        if ($type !== '' && $type !== 'email') {
            return 'related:'.$value;
        }

        return $value;
    }
}
