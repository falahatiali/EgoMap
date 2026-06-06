<?php

namespace App\Console\Commands;

use App\Services\Billing\BillingSchemaEnsurer;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'billing:ensure-schema', description: 'Ensure Cashier billing tables and user columns exist')]
class EnsureBillingSchemaCommand extends Command
{
    protected $signature = 'billing:ensure-schema';

    public function handle(BillingSchemaEnsurer $schema): int
    {
        $applied = $schema->ensure();

        if ($applied === []) {
            $this->components->info('Billing schema is already up to date.');

            return self::SUCCESS;
        }

        $this->components->info('Applied billing schema updates:');
        foreach ($applied as $change) {
            $this->line("  - {$change}");
        }

        return self::SUCCESS;
    }
}
