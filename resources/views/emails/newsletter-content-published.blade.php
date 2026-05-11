<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $headline }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Bonjour {{ $recipientName !== '' ? $recipientName : 'cher abonne' }},</p>

    <p><strong>{{ $headline }}</strong></p>

    <p>{{ $summary }}</p>

    <p>
        <a href="{{ $contentUrl }}" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:10px 14px;background:#1d4ed8;color:#ffffff;text-decoration:none;border-radius:6px;">
            Voir {{ $contentType === 'opportunity' ? 'l\'opportunite' : 'l\'actualite' }}
        </a>
    </p>

    <p>Ou copiez ce lien:</p>
    <p><a href="{{ $contentUrl }}">{{ $contentUrl }}</a></p>

    <p>Merci,<br>Nord Sud Action</p>
</body>
</html>
