<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Demo\EnsureDemoProspectPortal;
use Illuminate\Console\Command;

final class EnsureDemoProspectCommand extends Command
{
    protected $signature = 'fil:ensure-demo-prospect';

    protected $description = 'Create or repair demo prospect portal login (prospect@fil.test → Jane Smith Application)';

    public function handle(EnsureDemoProspectPortal $ensure): int
    {
        $result = $ensure->handle();

        $this->info(sprintf(
            'Prospect #%d linked to lead #%d (%s).',
            $result['prospect_id'],
            $result['lead_id'],
            $result['lead_title'],
        ));

        if (! $result['can_access']) {
            $this->error('Portal access check still failing — verify roles and lead pipeline_phase.');

            return self::FAILURE;
        }

        $this->info('Portal login: prospect@fil.test / password → /portal/login');

        return self::SUCCESS;
    }
}
