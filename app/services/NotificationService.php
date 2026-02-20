<?php



namespace App\services;

use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Envoie un email à l'admin (config notifications.email).
     */
    public function send(Mailable $mailable): void
    {
        if (! config('notifications.enabled', true)) {
            return;
        }

        $email = config('notifications.email');
        if (! $email) {
            Log::warning('Notification skipped: NOTIFICATION_EMAIL not configured');

            return;
        }

        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $e) {
            Log::error('Notification failed', [
                'mailable' => $mailable::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Envoie une notification à un utilisateur (mail + DB selon config).
     */
    public function notifyUser(User $user, Notification $notification): void
    {
        if (! config('notifications.enabled', true)) {
            return;
        }

        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::error('User notification failed', [
                'user_id' => $user->id,
                'notification' => $notification::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
