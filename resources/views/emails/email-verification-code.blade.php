<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vérifiez votre email — HeatAlert</title>
  <!--[if mso]><style>body{font-family:Arial,sans-serif!important}</style><![endif]-->
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Inter',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="580" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 32px rgba(0,0,0,0.08);">

          {{-- En-tête gradient rouge HeatAlert --}}
          <tr>
            <td style="background:linear-gradient(135deg,#dc2626,#b91c1c);padding:36px 40px;text-align:center;">
              <div style="display:inline-block;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);border-radius:12px;padding:12px 16px;margin-bottom:16px;">
                <span style="font-size:28px;">🌡️</span>
              </div>
              <h1 style="color:#ffffff;font-size:22px;font-weight:800;margin:0;letter-spacing:-0.5px;">HeatAlert</h1>
              <p style="color:rgba(255,255,255,0.75);font-size:13px;margin:6px 0 0;">Surveillance Météo Tunisie</p>
            </td>
          </tr>

          {{-- Corps --}}
          <tr>
            <td style="padding:40px 40px 32px;">
              <h2 style="color:#0f172a;font-size:20px;font-weight:700;margin:0 0 8px;">Bonjour {{ $prenom }} 👋</h2>
              <p style="color:#64748b;font-size:15px;line-height:1.7;margin:0 0 28px;">
                Merci de rejoindre <strong>HeatAlert</strong> ! Pour finaliser la création de votre compte et accéder à vos alertes météo personnalisées, veuillez saisir le code de vérification ci-dessous :
              </p>

              {{-- Code en grand --}}
              <div style="background:linear-gradient(135deg,#fef2f2,#fee2e2);border:2px solid #fecaca;border-radius:16px;padding:28px;text-align:center;margin-bottom:28px;">
                <p style="color:#991b1b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin:0 0 12px;">Code de vérification</p>
                <div style="background:#ffffff;border-radius:12px;padding:20px 32px;display:inline-block;box-shadow:0 4px 16px rgba(220,38,38,0.15);">
                  <span style="font-size:42px;font-weight:900;letter-spacing:12px;color:#dc2626;font-family:'Courier New',monospace;">{{ $code }}</span>
                </div>
                <p style="color:#b91c1c;font-size:12px;margin:14px 0 0;">⏱️ Ce code expire dans <strong>15 minutes</strong></p>
              </div>

              <div style="background:#f8fafc;border-left:4px solid #3b82f6;border-radius:0 8px 8px 0;padding:16px 18px;margin-bottom:24px;">
                <p style="color:#1e40af;font-size:13px;margin:0;font-weight:500;">
                  🔒 Si vous n'avez pas créé de compte sur HeatAlert, ignorez cet email.
                </p>
              </div>

              <p style="color:#94a3b8;font-size:12px;line-height:1.6;margin:0;">
                Cet email a été envoyé automatiquement depuis HeatAlert — Plateforme de surveillance thermique de la Tunisie.<br>
                Pour toute question, contactez notre support.
              </p>
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:20px 40px;text-align:center;">
              <p style="color:#94a3b8;font-size:12px;margin:0;">
                © {{ date('Y') }} HeatAlert — Tunisie. Tous droits réservés.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
