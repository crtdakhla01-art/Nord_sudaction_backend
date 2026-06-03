<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $headline }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Bonjour {{ $recipientName !== '' ? $recipientName : 'cher abonné' }},</p>

    <p><strong>{{ $headline }}</strong></p>

    <p>{{ $summary }}</p>

    <p>
        <a href="{{ $contentUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:10px 14px;background:#1d4ed8;color:#ffffff;text-decoration:none;border-radius:6px;">
            Voir {{ $contentType === 'opportunity' ? 'l\'opportunité' : 'l\'actualité' }}
        </a>
    </p>

    <p>Ou copiez ce lien :</p>
    <p><a href="{{ $contentUrl }}">{{ $contentUrl }}</a></p>

    <hr style="border:0;border-top:1px solid #e5e7eb;margin:20px 0;">
    <p style="font-size:12px;color:#6b7280;">
        Vous recevez cet email parce que vous êtes inscrit(e) à la newsletter Nord Sud Action.
    </p>
    <p style="font-size:12px;color:#6b7280;">
        <a href="{{ $unsubscribeUrl }}" target="_blank" rel="noopener noreferrer">Se désabonner</a>
    </p>

    <p>Merci,<br>Nord Sud Action</p>
</body>
</html>
