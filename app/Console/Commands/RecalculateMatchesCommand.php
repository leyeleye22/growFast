<?php



namespace App\Console\Commands;

use App\Mail\MatchesRecalculatedMail;
use App\Models\Startup;
use App\services\NotificationService;
use App\services\OpportunityMatchingService;
use Illuminate\Console\Command;

class RecalculateMatchesCommand extends Command
{
    protected $signature = 'matches:recalculate';

    protected $description = 'Recalculate opportunity matches for all startups';

    public function handle(OpportunityMatchingService $matchingService, NotificationService $notificationService): int
    {
        $this->info('Recalculating matches...');

        $matchingService->recalculateAll();

        $startupsCount = Startup::count();
        $notificationService->send(new MatchesRecalculatedMail($startupsCount));

        $this->info('Matches recalculated.');

        return self::SUCCESS;
    }
}
