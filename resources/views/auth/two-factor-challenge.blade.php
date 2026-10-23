<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Authentification à deux facteurs — HeatAlert</title>
  <meta name="description" content="Saisissez le code de votre application d'authentification pour accéder à HeatAlert.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="{{ asset('assets/front/img/favicon-heatalert.jpg') }}" rel="icon" type="image/jpeg">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="{{ asset('assets/front/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/front/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      min-height: 100%;
      font-family: 'Inter', sans-serif;
      background: #0b0f19;
      color: #f8fafc;
    }

    /* ── PAGE WRAPPER : CENTRÉ GLOBALEMENT ── */
    .twofa-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 24px;
      position: relative;
      overflow: hidden;
      background:
        radial-gradient(circle at 18% 22%, rgba(220, 38, 38, 0.16) 0%, transparent 45%),
        radial-gradient(circle at 82% 78%, rgba(37, 99, 235, 0.12) 0%, transparent 45%),
        radial-gradient(circle at 50% 50%, rgba(15, 23, 42, 0.8) 0%, transparent 70%),
        linear-gradient(135deg, #090d16 0%, #0f172a 60%, #0b1120 100%);
    }

    /* ── CONTENEUR PRINCIPAL CENTRÉ ET HARMONIEUX ── */
    .twofa-container {
      width: 100%;
      max-width: 1020px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 56px;
      position: relative;
      z-index: 2;
    }

    /* ── PANNEAU GAUCHE : BRANDING & INFOS ── */
    .twofa-brand {
      flex: 1;
      max-width: 480px;
    }

    .twofa-brand-logo {
      display: inline-flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 36px;
      text-decoration: none;
      transition: transform .2s ease;
    }
    .twofa-brand-logo:hover {
      transform: translateY(-2px);
    }
    .brand-logo-mark {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.35rem;
      color: #fff;
      box-shadow: 0 8px 24px rgba(220, 38, 38, .45);
    }
    .brand-logo-text {
      font-family: 'Montserrat', sans-serif;
      font-weight: 800;
      font-size: 1.45rem;
      color: #ffffff;
      letter-spacing: -0.5px;
    }

    .brand-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 2.2rem;
      font-weight: 900;
      color: #ffffff;
      line-height: 1.2;
      margin-bottom: 16px;
      letter-spacing: -0.5px;
    }
    .brand-title span {
      background: linear-gradient(135deg, #ef4444, #f97316);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .brand-sub {
      color: #94a3b8;
      font-size: .95rem;
      line-height: 1.7;
      margin-bottom: 36px;
    }

    /* Boîte Sécurité avec icône SVG nette */
    .shield-box {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 20px;
      padding: 26px 28px;
      backdrop-filter: blur(12px);
      box-shadow: 0 16px 36px rgba(0, 0, 0, 0.25);
    }

    .shield-badge-wrap {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 22px;
      padding-bottom: 18px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .shield-badge-icon {
      width: 50px;
      height: 50px;
      border-radius: 14px;
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.2), rgba(239, 68, 68, 0.1));
      border: 1px solid rgba(239, 68, 68, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      color: #ef4444;
      flex-shrink: 0;
      box-shadow: 0 6px 18px rgba(220, 38, 38, 0.25);
    }
    .shield-badge-text h6 {
      font-size: .95rem;
      font-weight: 700;
      color: #ffffff;
      margin: 0 0 2px;
    }
    .shield-badge-text p {
      font-size: .8rem;
      color: #94a3b8;
      margin: 0;
    }

    .shield-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .shield-list li {
      display: flex;
      align-items: center;
      gap: 12px;
      color: #cbd5e1;
      font-size: .88rem;
    }
    .shield-list li i {
      color: #10b981;
      font-size: .95rem;
      flex-shrink: 0;
    }

    /* ── PANNEAU DROIT : CARTE BLANCHE DU FORMULAIRE ── */
    .twofa-form-panel {
      flex: 0 0 440px;
      display: flex;
      justify-content: center;
    }

    .twofa-card {
      background: #ffffff;
      border-radius: 24px;
      padding: 46px 42px;
      width: 100%;
      box-shadow: 0 25px 70px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.1);
      position: relative;
      overflow: hidden;
    }

    /* Barre supérieure dégradée */
    .twofa-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: linear-gradient(90deg, #dc2626, #f97316, #dc2626);
      background-size: 200% 100%;
      animation: barGlow 3s linear infinite;
    }
    @keyframes barGlow {
      0% { background-position: 0% 0; }
      100% { background-position: 200% 0; }
    }

    /* Icône de cadenas stylée et épurée */
    .twofa-lock-badge {
      width: 66px;
      height: 66px;
      border-radius: 20px;
      background: linear-gradient(135deg, #fef2f2, #fee2e2);
      border: 2px solid #fecaca;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
      color: #dc2626;
      margin: 0 auto 20px;
      box-shadow: 0 8px 24px rgba(220, 38, 38, 0.18);
    }

    .twofa-card-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 1.5rem;
      font-weight: 800;
      color: #0f172a;
      text-align: center;
      margin-bottom: 6px;
      letter-spacing: -0.3px;
    }

    .twofa-card-sub {
      color: #64748b;
      font-size: .88rem;
      text-align: center;
      line-height: 1.55;
      margin-bottom: 22px;
    }

    /* Pilule applications supportées */
    .apps-pill {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 10px 14px;
      font-size: .82rem;
      color: #475569;
      margin-bottom: 24px;
      text-align: center;
    }
    .apps-pill i {
      font-size: .95rem;
    }

    /* ── INPUTS OTP (6 CASES) ── */
    .otp-inputs {
      display: flex;
      gap: 10px;
      justify-content: center;
      margin-bottom: 26px;
    }
    .otp-digit {
      width: 48px;
      height: 58px;
      border: 2px solid #e2e8f0;
      border-radius: 14px;
      text-align: center;
      font-size: 1.55rem;
      font-weight: 800;
      color: #0f172a;
      font-family: 'Montserrat', sans-serif;
      background: #f8fafc;
      transition: all .2s ease;
      outline: none;
      caret-color: #dc2626;
    }
    .otp-digit:focus {
      border-color: #dc2626;
      background: #ffffff;
      box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.14);
      transform: translateY(-2px);
    }
    .otp-digit.filled {
      border-color: #2563eb;
      background: #eff6ff;
      color: #1d4ed8;
    }
    .otp-digit.error {
      border-color: #ef4444;
      background: #fef2f2;
      color: #dc2626;
      animation: shake 0.4s ease;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-4px); }
      40%, 80% { transform: translateX(4px); }
    }

    /* Bouton d'action principal */
    .btn-twofa {
      width: 100%;
      border: none;
      border-radius: 14px;
      padding: .85rem 1.2rem;
      font-size: .95rem;
      font-weight: 700;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: all .25s ease;
      margin-bottom: 16px;
      background: #e2e8f0;
      color: #94a3b8;
      box-shadow: none;
    }
    .btn-twofa:not(:disabled) {
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      color: #ffffff;
      box-shadow: 0 8px 24px rgba(220, 38, 38, 0.35);
      cursor: pointer;
    }
    .btn-twofa:not(:disabled):hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 32px rgba(220, 38, 38, 0.5);
    }
    .btn-twofa:disabled {
      cursor: not-allowed;
      opacity: 0.85;
    }

    /* Message d'erreur */
    .alert-error {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #991b1b;
      border-radius: 12px;
      padding: 12px 16px;
      font-size: .85rem;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Lien retour */
    .back-link {
      display: flex;
      align-items: center;
      gap: 8px;
      color: #64748b;
      font-size: .85rem;
      font-weight: 500;
      text-decoration: none;
      justify-content: center;
      transition: color .2s ease;
      margin-top: 4px;
    }
    .back-link:hover {
      color: #dc2626;
    }

    /* ── RESPONSIVE ADAPTATIF ── */
    @media (max-width: 920px) {
      .twofa-container {
        flex-direction: column;
        justify-content: center;
        gap: 36px;
      }
      .twofa-brand {
        max-width: 440px;
        text-align: center;
      }
      .twofa-brand-logo {
        justify-content: center;
        margin-bottom: 20px;
      }
      .shield-box {
        display: none;
      }
      .twofa-form-panel {
        flex: 1;
        width: 100%;
        max-width: 440px;
      }
    }

    @media (max-width: 480px) {
      .twofa-card {
        padding: 34px 22px;
      }
      .otp-digit {
        width: 42px;
        height: 52px;
        font-size: 1.35rem;
      }
      .otp-inputs {
        gap: 6px;
      }
    }
  </style>
</head>
<body>
<div class="twofa-wrap">

  <div class="twofa-container">

    {{-- PANNEAU GAUCHE : Branding & Explications --}}
    <div class="twofa-brand">
      <a href="{{ route('home') }}" class="twofa-brand-logo">
        <div class="brand-logo-mark"><i class="fa-solid fa-temperature-high"></i></div>
        <span class="brand-logo-text">HeatAlert</span>
      </a>

      <h1 class="brand-title">
        Authentification<br><span>à deux facteurs</span>
      </h1>
      <p class="brand-sub">
        Votre compte est protégé par une couche de sécurité supplémentaire. Saisissez le code temporaire affiché sur votre application d'authentification.
      </p>

      <div class="shield-box">
        <div class="shield-badge-wrap">
          <div class="shield-badge-icon">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <div class="shield-badge-text">
            <h6>Protection TOTP Avancée</h6>
            <p>Sécurité maximale pour vos données météo</p>
          </div>
        </div>

        <ul class="shield-list">
          <li>
            <i class="fa-solid fa-circle-check"></i>
            <span>Code TOTP valide 30 secondes</span>
          </li>
          <li>
            <i class="fa-solid fa-circle-check"></i>
            <span>Compatible Google Authenticator</span>
          </li>
          <li>
            <i class="fa-solid fa-circle-check"></i>
            <span>Compatible Authy & Microsoft Authenticator</span>
          </li>
          <li>
            <i class="fa-solid fa-circle-check"></i>
            <span>Aucun SMS requis — 100% hors-ligne</span>
          </li>
        </ul>
      </div>
    </div>

    {{-- PANNEAU DROIT : Formulaire de saisie du Code --}}
    <div class="twofa-form-panel">
      <div class="twofa-card">

        <div class="twofa-lock-badge">
          <i class="fa-solid fa-lock"></i>
        </div>

        <h2 class="twofa-card-title">Code de vérification</h2>
        <p class="twofa-card-sub">
          Ouvrez votre application d'authentification<br>et saisissez le code à 6 chiffres.
        </p>

        <div class="apps-pill">
          <i class="fa-solid fa-mobile-screen-button text-danger"></i>
          <span>Google Authenticator · Authy · Microsoft</span>
        </div>

        @if(isset($errors) && $errors->any())
          <div class="alert-error">
            <i class="fa-solid fa-circle-exclamation text-danger flex-shrink-0"></i>
            <span>{{ $errors->first() }}</span>
          </div>
        @endif

        <form method="POST" action="{{ route('2fa.verify') }}" id="twofa-form">
          @csrf
          <input type="hidden" name="code" id="code-hidden">

          <div class="otp-inputs" id="otp-inputs">
            @for($i = 0; $i < 6; $i++)
              <input type="text"
                     class="otp-digit @if(isset($errors) && $errors->any()) error @endif"
                     maxlength="1"
                     inputmode="numeric"
                     pattern="[0-9]"
                     id="digit-{{ $i }}"
                     autocomplete="off"
                     aria-label="Chiffre {{ $i + 1 }}">
            @endfor
          </div>

          <button type="submit" class="btn-twofa" id="btn-submit" disabled>
            <i class="fa-solid fa-unlock-keyhole"></i>
            <span>Valider et se connecter</span>
          </button>
        </form>

        <a href="{{ route('login') }}" class="back-link">
          <i class="fa-solid fa-arrow-left"></i>
          <span>Retour à la connexion</span>
        </a>

      </div>
    </div>

  </div>

</div>

<script src="{{ asset('assets/front/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script>
  const digits = Array.from(document.querySelectorAll('.otp-digit'));
  const hidden = document.getElementById('code-hidden');
  const btnSub = document.getElementById('btn-submit');

  function updateHidden() {
    const code = digits.map(d => d.value).join('');
    hidden.value = code;
    btnSub.disabled = code.length !== 6;
    digits.forEach(d => {
      d.classList.toggle('filled', d.value !== '' && !d.classList.contains('error'));
    });
  }

  digits.forEach((d, i) => {
    d.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !d.value && i > 0) {
        digits[i - 1].focus();
        digits[i - 1].select();
      }
    });

    d.addEventListener('input', () => {
      d.value = d.value.replace(/\D/g, '').slice(-1);
      d.classList.remove('error');
      updateHidden();
      if (d.value && i < 5) {
        digits[i + 1].focus();
      }
    });

    d.addEventListener('paste', e => {
      e.preventDefault();
      const pasted = (e.clipboardData || window.clipboardData)
        .getData('text')
        .replace(/\D/g, '')
        .slice(0, 6);

      pasted.split('').forEach((c, j) => {
        if (digits[j]) {
          digits[j].value = c;
          digits[j].classList.remove('error');
        }
      });
      updateHidden();
      digits[Math.min(pasted.length, 5)].focus();
    });
  });

  // Auto-focus sur la première case
  if (digits[0]) {
    digits[0].focus();
  }

  // Nettoyage en cas d'erreur renvoyée
  @if(isset($errors) && $errors->any())
    digits.forEach(d => { d.value = ''; });
    if (digits[0]) digits[0].focus();
  @endif
</script>
</body>
</html>
