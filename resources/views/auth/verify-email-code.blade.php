<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vérification Email — HeatAlert</title>
  <meta name="description" content="Entrez le code reçu par email pour activer votre compte HeatAlert.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="{{ asset('assets/front/img/favicon-heatalert.jpg') }}" rel="icon" type="image/jpeg">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="{{ asset('assets/front/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/front/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; font-family: 'Inter', sans-serif; background: #f1f5f9; }

    .verify-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 16px;
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1a1a2e 100%);
    }

    .verify-card {
      background: #fff;
      border-radius: 24px;
      padding: 52px 48px;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 24px 80px rgba(0,0,0,0.35);
      position: relative;
      overflow: hidden;
    }
    .verify-card::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 5px;
      background: linear-gradient(90deg, #dc2626, #f97316, #dc2626);
      background-size: 200% 100%;
      animation: shimmer 2.5s linear infinite;
    }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

    .verify-icon {
      width: 72px; height: 72px;
      border-radius: 20px;
      background: linear-gradient(135deg, #fef2f2, #fee2e2);
      border: 2px solid #fecaca;
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem;
      margin: 0 auto 24px;
    }
    .verify-title {
      font-family: 'Montserrat', sans-serif;
      font-size: 1.6rem; font-weight: 800; color: #0f172a;
      text-align: center; margin-bottom: 8px;
    }
    .verify-sub {
      color: #64748b; font-size: .9rem; text-align: center;
      line-height: 1.6; margin-bottom: 32px;
    }
    .verify-sub strong { color: #dc2626; }

    /* Inputs OTP */
    .otp-inputs {
      display: flex; gap: 10px; justify-content: center;
      margin-bottom: 28px;
    }
    .otp-digit {
      width: 54px; height: 64px;
      border: 2.5px solid #e5e7eb;
      border-radius: 14px;
      text-align: center;
      font-size: 1.6rem; font-weight: 800; color: #0f172a;
      font-family: 'Montserrat', monospace;
      background: #f9fafb;
      transition: all .2s;
      outline: none;
      caret-color: #dc2626;
    }
    .otp-digit:focus {
      border-color: #dc2626;
      box-shadow: 0 0 0 4px rgba(220,38,38,0.12);
      background: #fff;
    }
    .otp-digit.filled { border-color: #16a34a; background: #f0fdf4; color: #15803d; }
    .otp-digit.error  { border-color: #ef4444; background: #fef2f2; }

    .btn-verify {
      width: 100%; border: none; border-radius: 14px;
      padding: .85rem; font-size: 1rem; font-weight: 700;
      font-family: 'Inter', sans-serif; cursor: pointer;
      background: linear-gradient(135deg, #dc2626, #b91c1c); color: #fff;
      box-shadow: 0 6px 24px rgba(220,38,38,.35);
      transition: .25s; display: flex; align-items: center; justify-content: center; gap: 8px;
      margin-bottom: 16px;
    }
    .btn-verify:hover { transform: translateY(-2px); box-shadow: 0 12px 36px rgba(220,38,38,.5); }
    .btn-verify:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

    .alert-error {
      background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
      border-radius: 12px; padding: 12px 16px; font-size: .85rem;
      margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
    }
    .alert-success {
      background: #f0fdf4; border: 1px solid #86efac; color: #166534;
      border-radius: 12px; padding: 12px 16px; font-size: .85rem;
      margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
    }

    .resend-row {
      text-align: center; font-size: .88rem; color: #64748b; margin-top: 4px;
    }
    .resend-link {
      color: #dc2626; font-weight: 700; text-decoration: none; cursor: pointer;
      background: none; border: none; font-family: 'Inter', sans-serif; font-size: .88rem;
    }
    .resend-link:hover { text-decoration: underline; }
    .resend-countdown { font-weight: 700; color: #dc2626; }

    .timer-bar {
      height: 4px; background: #e5e7eb; border-radius: 4px;
      margin-bottom: 24px; overflow: hidden;
    }
    .timer-bar-inner {
      height: 100%; background: linear-gradient(90deg, #22c55e, #dc2626);
      border-radius: 4px; width: 100%;
      transition: width 1s linear;
    }

    .back-link {
      display: flex; align-items: center; gap: 6px;
      color: #94a3b8; font-size: .83rem; text-decoration: none;
      justify-content: center; margin-top: 16px;
      transition: color .2s;
    }
    .back-link:hover { color: #dc2626; }

    .logo-top {
      text-align: center; margin-bottom: 28px;
    }
    .logo-top a {
      display: inline-flex; align-items: center; gap: 10px;
      text-decoration: none;
    }
    .logo-mark {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      border-radius: 10px; display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; color: #fff;
    }
    .logo-text { font-family: 'Montserrat', sans-serif; font-weight: 800; font-size: 1.1rem; color: #0f172a; }
  </style>
</head>
<body>
<div class="verify-wrap">
  <div class="verify-card">

    <div class="logo-top">
      <a href="{{ route('home') }}">
        <div class="logo-mark"><i class="fa-solid fa-temperature-high"></i></div>
        <span class="logo-text">HeatAlert</span>
      </a>
    </div>

    <div class="verify-icon">📧</div>
    <h1 class="verify-title">Vérifiez votre email</h1>
    <p class="verify-sub">
      Un code à <strong>6 chiffres</strong> a été envoyé à<br>
      <strong>{{ $email ?? session('pending_registration.data.email') ?? (auth()->user() ? auth()->user()->email : '') }}</strong>
    </p>

    {{-- Barre de temps restant (15 min = 900s) --}}
    <div class="timer-bar">
      <div class="timer-bar-inner" id="timer-bar"></div>
    </div>

    {{-- Alertes --}}
    @if(isset($errors) && $errors->any())
      <div class="alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        {{ $errors->first() }}
      </div>
    @endif

    @if(session('status') === 'code-resent')
      <div class="alert-success">
        <i class="fa-solid fa-circle-check"></i>
        Un nouveau code a été envoyé à votre adresse email.
      </div>
    @endif

    {{-- Formulaire code OTP --}}
    <form method="POST" action="{{ route('verification.code.verify') }}" id="otp-form">
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

      <button type="submit" class="btn-verify" id="btn-verify" disabled>
        <i class="fa-solid fa-shield-check"></i> Valider mon compte
      </button>
    </form>

    <div class="resend-row">
      Pas reçu ?
      <form method="POST" action="{{ route('verification.code.resend') }}" style="display:inline;">
        @csrf
        <button type="submit" class="resend-link" id="resend-btn" disabled>
          Renvoyer le code (<span class="resend-countdown" id="resend-countdown">60</span>s)
        </button>
      </form>
    </div>

    @if(auth()->check())
      <a href="{{ route('logout') }}" class="back-link"
         onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        <i class="fa-solid fa-arrow-left"></i> Se déconnecter
      </a>
      <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
    @else
      <a href="{{ route('register') }}" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> Recommencer l'inscription
      </a>
    @endif

  </div>
</div>

<script src="{{ asset('assets/front/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script>
  // ── OTP Digit Navigation
  const digits   = Array.from(document.querySelectorAll('.otp-digit'));
  const hidden   = document.getElementById('code-hidden');
  const btnVerif = document.getElementById('btn-verify');

  function getCode() { return digits.map(d => d.value).join(''); }

  function updateHidden() {
    const code = getCode();
    hidden.value = code;
    btnVerif.disabled = code.length !== 6;
    digits.forEach((d, i) => {
      d.classList.toggle('filled', d.value !== '');
    });
  }

  digits.forEach((d, i) => {
    d.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !d.value && i > 0) {
        digits[i - 1].focus();
        digits[i - 1].select();
      }
    });
    d.addEventListener('input', e => {
      d.value = d.value.replace(/\D/g, '').slice(-1);
      updateHidden();
      if (d.value && i < 5) digits[i + 1].focus();
    });
    d.addEventListener('paste', e => {
      e.preventDefault();
      const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
      pasted.split('').forEach((c, j) => { if (digits[j]) digits[j].value = c; });
      updateHidden();
      const next = Math.min(pasted.length, 5);
      digits[next].focus();
    });
  });

  // Auto-focus premier champ
  digits[0].focus();

  // ── Compte à rebours renvoyer (60s)
  const resendBtn = document.getElementById('resend-btn');
  const cdEl      = document.getElementById('resend-countdown');
  let cd = 60;
  const cdTimer = setInterval(() => {
    cd--;
    cdEl.textContent = cd;
    if (cd <= 0) {
      clearInterval(cdTimer);
      resendBtn.disabled = false;
      resendBtn.textContent = 'Renvoyer le code';
    }
  }, 1000);

  // ── Barre de progression (15 min = 900s)
  const bar = document.getElementById('timer-bar');
  let remaining = 900;
  const barTimer = setInterval(() => {
    remaining--;
    bar.style.width = (remaining / 900 * 100) + '%';
    if (remaining <= 0) clearInterval(barTimer);
  }, 1000);

  // ── Si erreur, secouer les inputs
  @if(isset($errors) && $errors->any())
    digits.forEach(d => { d.classList.add('error'); d.value = ''; });
    if (digits[0]) digits[0].focus();
  @endif
</script>
</body>
</html>
