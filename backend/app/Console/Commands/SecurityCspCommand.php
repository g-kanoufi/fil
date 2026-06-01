<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Security\ContentSecurityPolicyBuilder;
use Illuminate\Console\Command;

final class SecurityCspCommand extends Command
{
    protected $signature = 'security:csp';

    protected $description = 'Print the active Content-Security-Policy header for staging validation';

    public function handle(ContentSecurityPolicyBuilder $builder): int
    {
        if ($builder->build() === null) {
            $this->warn('CSP is disabled (FIL_CSP_ENABLED=false or APP_ENV=local).');

            return self::FAILURE;
        }

        $this->line(sprintf('%s: %s', $builder->headerName(), $builder->build()));

        if ((bool) config('fil-security.csp.report_only', false)) {
            $this->newLine();
            $this->comment('Report-only mode — violations appear in browser console but are not blocked.');
            $this->comment('Set FIL_CSP_REPORT_ONLY=false after ACH/FDD/widget flows are clean.');
        }

        return self::SUCCESS;
    }
}
