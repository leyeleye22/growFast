<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f59e0b; color: white; padding: 16px; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 8px 8px; }
        .step { font-weight: 600; color: #f59e0b; margin-bottom: 8px; }
        ul { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="header"><strong>GrowFast</strong> — Abonnement annulé</div>
    <div class="content">
        <div class="step">Étape : Annulation d'abonnement</div>
        <p>Un utilisateur a annulé son abonnement.</p>
        <ul>
            <li><strong>Utilisateur :</strong> {{ $userSubscription->user->name }} ({{ $userSubscription->user->email }})</li>
            <li><strong>Plan :</strong> {{ $userSubscription->subscription->name }}</li>
        </ul>
    </div>
</body>
</html>
