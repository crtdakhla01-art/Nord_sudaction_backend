<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle inscription Smara Discovery Experience</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;line-height:1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f4f6;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 28px 12px 28px;">
                            <h1 style="margin:0 0 20px 0;font-size:22px;line-height:1.3;color:#111827;font-weight:700;">
                                Nouvelle inscription Smara Discovery Experience
                            </h1>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                        <strong style="color:#111827;">Nom et prénom :</strong>
                                        <span style="color:#374151;"> {{ $fullName }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                        <strong style="color:#111827;">Ville de résidence :</strong>
                                        <span style="color:#374151;"> {{ $city }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                        <strong style="color:#111827;">Téléphone :</strong>
                                        <span style="color:#374151;" dir="ltr"> {{ $phone }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                        <strong style="color:#111827;">E-mail :</strong>
                                        <span style="color:#374151;" dir="ltr"> {{ $email }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                        <strong style="color:#111827;">Tranche d'âge :</strong>
                                        <span style="color:#374151;"> {{ $ageGroup }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                        <strong style="color:#111827;">A déjà visité Es-Smara :</strong>
                                        <span style="color:#374151;"> {{ $hasVisitedEsSmara }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                        <strong style="color:#111827;">Niveau d'intérêt :</strong>
                                        <span style="color:#374151;"> {{ $interestLevel }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                        <strong style="color:#111827;">Nombre de participants :</strong>
                                        <span style="color:#374151;"> {{ $participantsCount }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                        <strong style="color:#111827;">Durée préférée :</strong>
                                        <span style="color:#374151;"> {{ $preferredDuration }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                        <strong style="color:#111827;">Activités sélectionnées :</strong>
                                        <span style="color:#374151;"> {{ $activities }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                        <strong style="color:#111827;">Informé(e) en priorité de la première date :</strong>
                                        <span style="color:#374151;"> {{ $notifyFirstDate }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 0;">
                                        <strong style="color:#111827;">Date de soumission :</strong>
                                        <span style="color:#374151;" dir="ltr"> {{ $submittedAt }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 28px 28px 28px;">
                            <p style="margin:16px 0 0 0;font-size:12px;color:#6b7280;">
                                Notification automatique — Association Nord Sud Action
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
