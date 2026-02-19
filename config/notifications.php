<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email de notification (admin)
    |--------------------------------------------------------------------------
    |
    | Adresse(s) email qui recevront les notifications admin à chaque étape
    | (inscription, abonnement, scraping, matching, etc.)
    |
    */
    'email' => env('NOTIFICATION_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@example.com')),

    /*
    |--------------------------------------------------------------------------
    | Activer les notifications
    |--------------------------------------------------------------------------
    */
    'enabled' => env('NOTIFICATIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Canaux internes (utilisateur)
    |--------------------------------------------------------------------------
    |
    | Canaux pour les notifications internes : mail, database, ou les deux.
    |
    */
    'user_channels' => ['mail', 'database'],

];
