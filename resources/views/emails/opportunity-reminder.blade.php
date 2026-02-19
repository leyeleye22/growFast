<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: system-ui, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f59e0b; color: white; padding: 16px; border-radius: 8px 8px 0 0; }
        .content { background: #fffbeb; padding: 20px; border: 1px solid #fde68a; border-top: none; border-radius: 0 0 8px 8px; }
        .cta { display: inline-block; margin-top: 16px; padding: 12px 24px; background: #f59e0b; color: white !important; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .deadline { color: #b45309; font-weight: 600; }
    </style>
</head>
<body>
    <div class="header"><strong>GrowFast</strong> — Rappel opportunité</div>
    <div class="content">
        <p>Bonjour {{ $user->name }},</p>
        <p>Vous aviez sauvegardé une opportunité qui pourrait vous intéresser. N'oubliez pas de candidater avant la date limite !</p>
        <p><strong>{{ $savedOpportunity->opportunity->title }}</strong></p>
        @if($savedOpportunity->opportunity->deadline)
            <p class="deadline">Date limite : {{ $savedOpportunity->opportunity->deadline->format('d/m/Y') }}</p>
        @endif
        @if($savedOpportunity->opportunity->external_url)
            <a href="{{ $savedOpportunity->opportunity->external_url }}" class="cta">Voir l'opportunité</a>
        @endif
    </div>
</body>
</html>
