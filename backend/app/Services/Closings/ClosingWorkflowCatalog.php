<?php

declare(strict_types=1);

namespace App\Services\Closings;

final class ClosingWorkflowCatalog
{
    /**
     * @return array<string, array{label: string, next: list<string>}>
     */
    public function statuses(): array
    {
        /** @var array<string, array{label: string, next: list<string>}> $statuses */
        $statuses = config('fil-closings.statuses', []);

        return $statuses;
    }

    public function label(string $status): string
    {
        return $this->statuses()[$status]['label'] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * @return list<string>
     */
    public function allowedNextStatuses(string $current): array
    {
        return $this->statuses()[$current]['next'] ?? [];
    }

    public function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        return in_array($to, $this->allowedNextStatuses($from), true);
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function transitionOptions(string $current): array
    {
        return array_map(
            fn (string $key): array => ['key' => $key, 'label' => $this->label($key)],
            $this->allowedNextStatuses($current),
        );
    }

    /**
     * @param  list<array{label: string, amount_cents: int}>|null  $lines
     * @return list<array{label: string, amount_cents: int}>
     */
    public function normalizeFeeLines(?array $lines): array
    {
        if ($lines === null) {
            return [];
        }

        $normalized = [];

        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $label = trim((string) ($line['label'] ?? ''));
            $amount = $line['amount_cents'] ?? null;

            if ($label === '' || ! is_numeric($amount)) {
                continue;
            }

            $normalized[] = [
                'label' => $label,
                'amount_cents' => max(0, (int) $amount),
            ];
        }

        return $normalized;
    }

    /**
     * @return list<array{label: string, amount_cents: int}>
     */
    public function feeLinesFromExtras(?array $extras): array
    {
        if ($extras === null) {
            return [];
        }

        $fees = $extras['fees'] ?? null;

        return is_array($fees) ? $this->normalizeFeeLines($fees) : [];
    }
}
