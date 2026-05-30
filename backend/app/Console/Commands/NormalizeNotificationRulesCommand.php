<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NotificationRule;
use App\Services\Notifications\NotificationConditionNormalizer;
use App\Services\Notifications\NotificationScheduleNormalizer;
use Illuminate\Console\Command;

final class NormalizeNotificationRulesCommand extends Command
{
    protected $signature = 'notifications:normalize-rules {--dry-run : Preview without saving}';

    protected $description = 'Normalize legacy notification conditionals and schedules to FIL v2 schema';

    public function handle(
        NotificationConditionNormalizer $conditionNormalizer,
        NotificationScheduleNormalizer $scheduleNormalizer,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        NotificationRule::query()->orderBy('id')->each(function (NotificationRule $rule) use (
            $conditionNormalizer,
            $scheduleNormalizer,
            $dryRun,
            &$updated,
        ): void {
            $changes = [];

            $rawConditionals = $rule->conditionals
                ?? $rule->extras['zrz_conditionals']
                ?? $rule->config['extras']['zrz_conditionals']
                ?? null;

            if (is_array($rawConditionals)) {
                $normalized = $conditionNormalizer->normalize($rawConditionals);

                if ($normalized !== null && $normalized !== $rule->conditionals) {
                    $changes['conditionals'] = $normalized;
                }
            }

            $rawSchedule = $rule->schedule
                ?? $rule->extras['schedule']
                ?? $rule->config['extras']['schedule']
                ?? $rule->config['schedule']
                ?? null;

            if (is_array($rawSchedule)) {
                $normalizedSchedule = $scheduleNormalizer->normalize($rawSchedule);

                if ($normalizedSchedule !== null && $normalizedSchedule !== $rule->schedule) {
                    $changes['schedule'] = $normalizedSchedule;
                }
            }

            if ($changes === []) {
                return;
            }

            $updated++;

            if ($dryRun) {
                $this->line("Would update rule #{$rule->id} ({$rule->title})");

                return;
            }

            $rule->update($changes);
        });

        $this->info($dryRun
            ? "Would normalize {$updated} rule(s)."
            : "Normalized {$updated} rule(s).");

        return self::SUCCESS;
    }
}
