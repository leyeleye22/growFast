<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OpportunityMatchingService;
use Illuminate\Console\Command;

class RecalculateMatchesCommand extends Command
{
    protected $signature = 'matches:recalculate';

    protected $description = 'Recalculate opportunity matches for all startups';

    public function handle(OpportunityMatchingService $matchingService): int
    {
        $this->info('Recalculating matches...');

        $matchingService->recalculateAll();

        $this->info('Matches recalculated.');

        return self::SUCCESS;
    }
}
