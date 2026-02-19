<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #22c55e; color: white; padding: 16px; border-radius: 8px 8px 0 0; }
        .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 8px 8px; }
        .step { font-weight: 600; color: #22c55e; margin-bottom: 8px; }
        ul { margin: 10px 0; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="header"><strong>GrowFast</strong> — Scraping terminé</div>
    <div class="content">
        <div class="step">Étape : Fin du scraping</div>
        <p>Le scraping s'est terminé avec succès.</p>
        <ul>
            <li><strong>Entrées trouvées :</strong> {{ $entriesFound }}</li>
            <li><strong>Jobs dispatchés :</strong> {{ $jobsDispatched }}</li>
            <li><strong>Déclenché par :</strong> {{ $triggeredBy === 'api' ? 'API' : 'Cron' }}</li>
        </ul>
    </div>
</body>
</html>
