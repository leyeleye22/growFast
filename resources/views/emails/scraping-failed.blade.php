<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ef4444; color: white; padding: 16px; border-radius: 8px 8px 0 0; }
        .content { background: #fef2f2; padding: 20px; border: 1px solid #fecaca; border-top: none; border-radius: 0 0 8px 8px; }
        .step { font-weight: 600; color: #ef4444; margin-bottom: 8px; }
        .error { background: #fff; padding: 12px; border-radius: 4px; font-family: monospace; font-size: 12px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="header"><strong>GrowFast</strong> — Erreur de scraping</div>
    <div class="content">
        <div class="step">Étape : Échec du scraping</div>
        <p>Une erreur s'est produite lors du scraping.</p>
        @if($sourceName)
            <p><strong>Source :</strong> {{ $sourceName }}</p>
        @endif
        <p><strong>Erreur :</strong></p>
        <div class="error">{{ $errorMessage }}</div>
    </div>
</body>
</html>
