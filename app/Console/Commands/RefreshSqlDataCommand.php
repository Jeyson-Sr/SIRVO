<?php

namespace App\Console\Commands;

use App\Modules\Oee\Models\OeeProduction;
use App\Modules\Oee\Models\OeeSku;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('db:refresh {--force : Run without confirmation}')]
#[Description('Delete production and SKU data from SQL, keeping stop codes, users, and teams')]
class RefreshSqlDataCommand extends Command
{
    /**
     * Wipe operational SQL data while preserving the stop-code catalog and accounts.
     */
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm($this->confirmationPrompt())) {
            $this->components->warn('Cancelled.');

            return self::SUCCESS;
        }

        $productions = OeeProduction::query()->count();
        $skus = OeeSku::query()->count();

        OeeProduction::query()->delete();
        OeeSku::query()->delete();

        $this->components->info("Deleted {$productions} productions and {$skus} SKUs. Stop codes, users, and teams were kept.");

        return self::SUCCESS;
    }

    /**
     * The confirmation shown before wiping data.
     */
    private function confirmationPrompt(): string
    {
        return 'This will delete all production and SKU data. Stop codes, users, and teams stay. Continue?';
    }
}
