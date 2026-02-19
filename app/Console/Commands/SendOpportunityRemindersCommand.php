<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Notifications\OpportunityReminderNotification;
use App\Models\SavedOpportunity;
use Illuminate\Console\Command;

class SendOpportunityRemindersCommand extends Command
{
    protected $signature = 'opportunities:send-reminders';

    protected $description = 'Envoie des rappels par email et DB aux utilisateurs ayant sauvegardé des opportunités avec deadline proche';

    public function handle(): int
    {
        if (! config('notifications.enabled', true)) {
            $this->info('Notifications désactivées.');

            return self::SUCCESS;
        }

        $this->info('Envoi des rappels opportunités...');

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
                $this->error("Erreur pour {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Rappels envoyés : {$sent}");

        return self::SUCCESS;
    }
}
