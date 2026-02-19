<?php



namespace App\Console\Commands;

use App\Notifications\OpportunityReminderNotification;
use App\Models\SavedOpportunity;
use Illuminate\Console\Command;

class SendOpportunityRemindersCommand extends Command
{
    protected $signature = 'opportunities:send-reminders';

    protected $description = 'Sends email and DB reminders to users who saved opportunities with upcoming deadlines';

    public function handle(): int
    {
        if (! config('notifications.enabled', true)) {
            $this->info('Notifications disabled.');

            return self::SUCCESS;
        }

        $this->info('Sending opportunity reminders...');

        $toRemind = SavedOpportunity::query()
            ->with(['opportunity', 'startup.user'])
            ->whereHas('opportunity', fn ($q) => $q
                ->withoutGlobalScopes()
                ->whereNotNull('deadline')
                ->where('deadline', '>=', now())
                ->where('deadline', '<=', now()->addDays(7))
            )
            ->where(function ($q): void {
                $q->whereNull('last_reminder_at')
                    ->orWhere('last_reminder_at', '<', now()->subDays(3));
            })
            ->get();

        $sent = 0;
        foreach ($toRemind as $saved) {
            $user = $saved->startup->user;
            if (! $user?->email) {
                continue;
            }

            try {
                $user->notify(new OpportunityReminderNotification($saved));
                $saved->update(['last_reminder_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                $this->error("Error for {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
