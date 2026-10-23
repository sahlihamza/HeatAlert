@extends('layouts.front.front')

@section('content')
<div class="page-title position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(15, 23, 42, .88), rgba(154, 52, 18, .82)), url('{{ asset('assets/front/img/health/emergency-1.webp') }}') center/cover; padding-top:135px; padding-bottom:45px;">
  <div class="container text-center">
    <span class="badge rounded-pill bg-warning text-dark px-3 py-2 mb-3"><i class="bi bi-lightning-charge-fill me-1"></i>Réseau électrique</span>
    <h1 class="heading-title text-white fw-bold">Coupures électriques</h1>
    <p class="text-white-50 mb-0">Consultez les coupures de votre zone et anticipez les interruptions de courant.</p>
  </div>
  <nav class="breadcrumbs mt-4" style="background:rgba(0,0,0,.3);">
    <div class="container"><ol class="mb-0"><li><a href="{{ route('front.home') }}" class="text-white-50">Accueil</a></li><li class="current text-white">Coupures</li></ol></div>
  </nav>
</div>

<section class="py-4 bg-white border-bottom">
  <div class="container">
    <form method="GET" action="{{ route('front.coupures.index') }}" class="row g-3 align-items-end">
      <div class="col-lg-3 col-md-6">
        <label for="type" class="form-label small fw-bold text-muted">Type de coupure</label>
        <select id="type" name="type" class="form-select">
          <option value="">Tous les types</option>
          @foreach($types as $type)
            <option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst($type) }}</option>
          @endforeach
        </select>
      </div>
      @auth
      <div class="col-lg-3 col-md-6">
        <label for="statut" class="form-label small fw-bold text-muted">Statut</label>
        <select id="statut" name="statut" class="form-select">
          <option value="">Tous les statuts</option>
          @foreach($statuts as $statut)
            <option value="{{ $statut }}" @selected(request('statut') === $statut)>{{ ucfirst(str_replace('_', ' ', $statut)) }}</option>
          @endforeach
        </select>
      </div>
      @endauth
      <div class="col-lg-3 col-md-6">
        <label for="tri" class="form-label small fw-bold text-muted">Trier par date</label>
        <select id="tri" name="tri" class="form-select">
          <option value="plus_proche" @selected(request('tri', 'plus_proche') === 'plus_proche')>Date la plus proche</option>
          <option value="plus_lointaine" @selected(request('tri') === 'plus_lointaine')>Date la plus éloignée</option>
        </select>
      </div>
      <div class="col-lg-3 col-md-6 d-flex gap-2">
        <button type="submit" class="btn btn-danger flex-grow-1"><i class="bi bi-funnel me-1"></i>Filtrer</button>
        <a href="{{ route('front.coupures.index') }}" class="btn btn-outline-secondary" title="Réinitialiser"><i class="bi bi-arrow-counterclockwise"></i></a>
      </div>
    </form>
  </div>
</section>

<section class="py-5" style="background:#f8fafc;">
  <div class="container">
    @auth
      @if($userZone && $zonePrediction && ($zonePrediction->probability >= 0.50 || $zonePrediction->risk_level === 'high' || $zonePrediction->risk_level === 'medium'))
        @php
          $probPct = round($zonePrediction->probability * 100, 1);
          $isHighRisk = $zonePrediction->probability >= 0.70 || $zonePrediction->risk_level === 'high';
          $cardBg = $isHighRisk ? '#fff5f5' : '#fffbeb';
          $borderCol = $isHighRisk ? '#fca5a5' : '#fcd34d';
          $accentCol = $isHighRisk ? '#dc2626' : '#d97706';
          $badgeClass = $isHighRisk ? 'bg-danger' : 'bg-warning text-dark';
        @endphp
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="border-left: 5px solid {{ $accentCol }} !important; background: {{ $cardBg }}; border: 1px solid {{ $borderCol }};">
          <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
              <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $badgeClass }} rounded-pill px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                  <i class="bi bi-shield-exclamation me-1"></i>Alerte Anticipez vos pannes
                </span>
                <span class="text-muted small">
                  <i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ $userZone->nom }} ({{ $userZone->ville }})
                </span>
              </div>
              @if($zonePrediction->created_at)
                <span class="small text-muted">
                  <i class="bi bi-clock-history me-1"></i>Mise à jour {{ $zonePrediction->created_at->diffForHumans() }}
                </span>
              @endif
            </div>

            <div class="row g-3 align-items-center">
                @php
                  $dateText = '';
                  if ($zonePrediction->estimated_outage_start && $zonePrediction->estimated_outage_end) {
                      $start = $zonePrediction->estimated_outage_start;
                      $end = $zonePrediction->estimated_outage_end;
                      if ($start->isToday()) {
                          $dayLabel = "aujourd'hui";
                      } elseif ($start->isTomorrow()) {
                          $dayLabel = "demain (" . $start->format('d/m') . ")";
                      } else {
                          $dayLabel = "le " . $start->format('d/m/Y');
                      }
                      $dateText = $dayLabel . " entre " . $start->format('H:i') . " et " . $end->format('H:i');
                  }
                @endphp
              <div class="col-lg-8">
                <h3 class="h5 fw-bold mb-2" style="color: {{ $accentCol }};">
                  <i class="bi bi-lightning-charge-fill me-1"></i>Votre zone ({{ $userZone->nom }}) présente un risque de coupure de {{ number_format($probPct, 1, ',', ' ') }}%
                  @if($dateText)
                    {{ $dateText }}.
                  @else
                    dans les prochaines 24 heures.
                  @endif
                </h3>
                <p class="mb-2 text-dark small" style="line-height: 1.5;">
                  Cette prévision est calculée en temps réel selon les fortes chaleurs et la demande électrique estimée sur le réseau de votre région.
                </p>
                <div class="d-flex flex-wrap gap-2 pt-1 mb-3">
                  <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs small">
                    <i class="bi bi-phone-fill text-primary me-1"></i>Rechargez vos téléphones et batteries
                  </span>
                  <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs small">
                    <i class="bi bi-snow text-info me-1"></i>Gardez les réfrigérateurs bien fermés
                  </span>
                  <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs small">
                    <i class="bi bi-water text-primary me-1"></i>Prévoyez des réserves d'eau potable
                  </span>
                </div>

                {{-- 🎙️ LECTEUR AUDIO D'ALERTE VOCALE EN DIALECTE TUNISIEN (DERJA) --}}
                <div class="p-3 rounded-4 bg-white border d-flex flex-wrap align-items-center justify-content-between gap-3 shadow-xs" style="border-color: {{ $borderCol }} !important;">
                  <div class="d-flex align-items-center gap-3">
                    <button type="button" id="btnPlayVoiceAlert" class="btn btn-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width:48px;height:48px;flex-shrink:0;" title="Écouter le bulletin vocal">
                      <i class="bi bi-play-fill fs-3" id="iconPlayVoice"></i>
                    </button>
                    <div>
                      <div class="fw-bold text-dark d-flex align-items-center gap-2">
                        <span id="voiceTitleText">🎙️ Écouter le Bulletin Vocal en Derja</span>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small" style="font-size:10px;">🇹🇳 بالدارجة</span>
                      </div>
                      <div class="small text-muted" id="voiceStatusText">
                        Alerte audio synthétisée par IA pour cette zone.
                      </div>
                    </div>
                  </div>

                  <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group">
                      <button type="button" class="btn btn-outline-danger active js-voice-lang" data-lang="ar-TN">
                        🇹🇳 تونسي
                      </button>
                      <button type="button" class="btn btn-outline-danger js-voice-lang" data-lang="fr">
                        🇫🇷 Français
                      </button>
                    </div>
                    <audio id="audioVoiceAlert" preload="none" class="d-none"></audio>
                  </div>
                </div>

              </div>

              <div class="col-lg-4 text-lg-end text-start">
                <div class="d-inline-flex flex-column align-items-lg-end">
                  <div class="display-6 fw-bold mb-0" style="color: {{ $accentCol }}; line-height: 1;">
                    {{ number_format($probPct, 1, ',', ' ') }}%
                  </div>
                  <span class="small text-muted fw-semibold">Niveau de risque {{ $isHighRisk ? 'Élevé' : 'Modéré' }}</span>
                  <a href="{{ route('front.zones.show', $userZone) }}" class="btn btn-sm btn-outline-danger rounded-pill mt-3 px-3">
                    <i class="bi bi-graph-up-arrow me-1"></i>Voir les détails météo
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      @endif
    @endauth

    {{-- 🗺️ Cartographie Nationale Interactive du Risque de Coupure (Citoyen) --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden bg-white">
      <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
          <div style="width:36px;height:36px;border-radius:10px;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
            <i class="bi bi-map-fill"></i>
          </div>
          <div>
            <h5 class="fw-bold mb-0" style="color: #1e3a5f; font-size: 1.05rem;">
              Carte Nationale de Surveillance & Risque de Coupure
            </h5>
            <span class="text-muted small">Surveillance thermique en temps réel et probabilité de surcharge électrique</span>
          </div>
        </div>
        <div class="d-flex align-items-center gap-3 small">
          <span class="d-flex align-items-center gap-1">
            <span class="d-inline-block rounded-circle" style="width:10px;height:10px;background:#dc2626;box-shadow:0 0 0 2px rgba(220,38,38,0.25);"></span>
            <span class="text-danger fw-semibold">Risque Élevé (&ge; 70%)</span>
          </span>
          <span class="d-flex align-items-center gap-1">
            <span class="d-inline-block rounded-circle" style="width:10px;height:10px;background:#f59e0b;box-shadow:0 0 0 2px rgba(245,158,11,0.25);"></span>
            <span class="text-warning fw-semibold">Modéré</span>
          </span>
          <span class="d-flex align-items-center gap-1">
            <span class="d-inline-block rounded-circle" style="width:10px;height:10px;background:#10b981;box-shadow:0 0 0 2px rgba(16,185,129,0.25);"></span>
            <span class="text-success fw-semibold">Faible</span>
          </span>
        </div>
      </div>
      <div class="card-body p-0 position-relative">
        <div id="frontCoupuresRiskMap" style="height: 380px; width: 100%; background: #e2e8f0;"></div>
      </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h2 class="h4 mb-1">{{ auth()->check() ? 'Les coupures de ma zone' : 'Coupures en cours' }}</h2>
        <p class="text-muted mb-0"><span id="coupures-count">{{ $coupures->total() }}</span> résultat(s)</p>
      </div>
      @auth
        <a href="{{ route('front.coupures.signaler') }}" class="btn btn-danger rounded-pill shadow-sm">
          <i class="bi bi-megaphone-fill me-2"></i>Signaler une coupure
        </a>
      @else
        <a href="{{ route('login') }}" class="btn btn-outline-danger rounded-pill">
          <i class="bi bi-person-lock me-2"></i>Se connecter pour signaler
        </a>
      @endauth
    </div>

    <div id="coupures-results" class="row g-4">
      @forelse($coupures as $coupure)
        <div class="col-lg-6 coupure-item" data-type="{{ $coupure->type }}" data-statut="{{ $coupure->statut }}" data-date="{{ $coupure->date_debut?->timestamp ?? 0 }}">
          @include('front.coupures._carte_coupure', ['coupure' => $coupure])
        </div>
      @empty
        <div class="col-12"><div class="text-center bg-white rounded-4 p-5 shadow-sm"><i class="bi bi-lightning-charge text-muted" style="font-size:3rem;"></i><h3 class="h5 mt-3">Aucune coupure trouvée</h3><p class="text-muted mb-0">Aucune coupure ne correspond à votre recherche.</p></div></div>
      @endforelse
    </div>

    <div id="coupures-no-results" class="text-center bg-white rounded-4 p-5 shadow-sm" style="display:none;">
      <i class="bi bi-funnel text-muted" style="font-size:3rem;"></i>
      <h3 class="h5 mt-3">Aucune coupure ne correspond aux filtres.</h3>
    </div>

    @if($coupures->hasPages())
      <div id="coupures-pagination" class="d-flex justify-content-center mt-5">{{ $coupures->links() }}</div>
    @endif
  </div>
</section>

<section class="py-4" style="background:#f8fafc;">
  <div class="container">
    <div class="bg-white rounded-4 shadow-sm p-3 p-md-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
          <h2 class="h4 mb-1"><i class="bi bi-calendar3 text-danger me-2"></i>Calendrier des coupures prévues</h2>
          <p class="text-muted mb-0">Les coupures prévues dans toutes les zones au cours des 7 prochains jours.</p>
        </div>
        <span class="badge rounded-pill bg-warning text-dark"><i class="bi bi-clock me-1"></i>7 prochains jours</span>
      </div>
      <div id="coupures-calendar"></div>
      @if($calendrierEvents->isEmpty())
        <p class="text-center text-muted small mb-0 mt-3">Aucune coupure prévue sur cette période.</p>
      @endif
    </div>
  </div>
</section>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
  #coupures-calendar { min-height: 520px; }
  #coupures-calendar .fc-toolbar-title { font-size: 1.1rem; color: #1e3a5f; }
  #coupures-calendar .fc-button-primary { background: #dc2626; border-color: #dc2626; }
  #coupures-calendar .fc-button-primary:hover { background: #b91c1c; border-color: #b91c1c; }
  #coupures-calendar .fc-event { cursor: pointer; border-radius: 6px; padding: 2px 4px; }
  @media (max-width: 576px) {
    #coupures-calendar { min-height: 600px; }
    #coupures-calendar .fc-toolbar { display: block; }
    #coupures-calendar .fc-toolbar-chunk { margin-bottom: 8px; }
  }

  /* Animations radar et style marqueurs */
  @keyframes citizenRadarPulse {
    0% { transform: scale(0.9); opacity: 0.9; }
    70% { transform: scale(1.8); opacity: 0; }
    100% { transform: scale(2.2); opacity: 0; }
  }
  .citizen-radar-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    animation: citizenRadarPulse 1.8s infinite ease-out;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const calendarElement = document.getElementById('coupures-calendar');
    if (calendarElement) {
      const calendar = new FullCalendar.Calendar(calendarElement, {
        locale: 'fr',
        initialView: 'listWeek',
        height: 'auto',
        firstDay: 1,
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: '',
        },
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        events: @json($calendrierEvents),
        eventClick: function (info) {
          info.jsEvent.preventDefault();
          if (info.event.url) window.location.href = info.event.url;
        },
      });
      calendar.render();
    }

    // Carte Leaflet Risque de Coupure Citoyen
    const frontMapEl = document.getElementById('frontCoupuresRiskMap');
    if (frontMapEl && typeof L !== 'undefined') {
      const mapZones = @json($mapZones ?? []);
      const map = L.map('frontCoupuresRiskMap', {
        center: [34.0, 9.5],
        zoom: 6.5,
        scrollWheelZoom: false,
      });

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap contributors',
      }).addTo(map);

      const bounds = [];

      mapZones.forEach(function (zone) {
        if (!zone.lat || !zone.lng) return;
        bounds.push([zone.lat, zone.lng]);

        const prob = zone.probability !== null ? Math.round(zone.probability * 1000) / 10 : null;
        let color = '#64748b';
        let fillColor = '#94a3b8';
        let riskLabel = 'Donnée manquante';
        let isCritical = false;

        if (prob !== null) {
          if (prob >= 70 || zone.risk_level === 'high') {
            color = '#dc2626';
            fillColor = '#ef4444';
            riskLabel = 'Risque Élevé';
            isCritical = true;
          } else if (prob >= 40 || zone.risk_level === 'medium') {
            color = '#d97706';
            fillColor = '#f59e0b';
            riskLabel = 'Risque Modéré';
          } else {
            color = '#059669';
            fillColor = '#10b981';
            riskLabel = 'Risque Faible';
          }
        }

        const markerHtml = `
          <div style="position:relative;width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
            ${isCritical ? `<div class="citizen-radar-ring" style="background:${color};opacity:0.4;"></div>` : ''}
            <div style="width:22px;height:22px;border-radius:50%;background:${fillColor};border:2.5px solid #ffffff;box-shadow:0 3px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;color:#ffffff;font-size:10px;font-weight:bold;">
              <i class="bi bi-lightning-charge-fill" style="font-size:10px;"></i>
            </div>
          </div>
        `;

        const customIcon = L.divIcon({
          html: markerHtml,
          className: 'front-coupure-marker',
          iconSize: [28, 28],
          iconAnchor: [14, 14],
          popupAnchor: [0, -14],
        });

        const marker = L.marker([zone.lat, zone.lng], { icon: customIcon }).addTo(map);

        L.circle([zone.lat, zone.lng], {
          radius: isCritical ? 25000 : 15000,
          color: color,
          weight: isCritical ? 2 : 1.5,
          fillColor: fillColor,
          fillOpacity: isCritical ? 0.22 : 0.1,
        }).addTo(map);

        const popupHtml = `
          <div style="min-width:210px;font-family:Inter,system-ui,sans-serif;">
            <div style="padding:10px 14px;background:${isCritical ? '#fee2e2' : '#f8fafc'};border-bottom:1px solid #e2e8f0;border-radius:12px 12px 0 0;">
              <span style="font-size:11px;font-weight:700;text-transform:uppercase;color:${color};display:inline-block;margin-bottom:2px;">
                ⚡ ${riskLabel} ${prob !== null ? '(' + prob + '%)' : ''}
              </span>
              <h4 style="margin:0;font-size:14px;font-weight:800;color:#0f172a;">${zone.nom}</h4>
              <span style="font-size:11px;color:#64748b;">${zone.ville} (${zone.gouvernorat})</span>
            </div>
            <div style="padding:10px 14px;font-size:12px;color:#334155;">
              ${zone.outage_start ? `
                <div style="margin-bottom:6px;">
                  <span style="color:#64748b;font-size:11px;">Créneau estimé :</span><br>
                  <strong style="color:${color};">${zone.outage_start} → ${zone.outage_end}</strong>
                </div>
              ` : ''}
              ${zone.cause ? `<div style="font-size:11px;color:#64748b;font-style:italic;margin-bottom:8px;">${zone.cause}</div>` : ''}
              <a href="${zone.url}" class="btn btn-sm btn-outline-primary w-100 rounded-pill py-1" style="font-size:11px;font-weight:600;">
                Voir la zone & météo →
              </a>
            </div>
          </div>
        `;

        marker.bindPopup(popupHtml);
      });

      if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [30, 30] });
      }
    }

    // 🎙️ GESTION DU BULLETIN VOCAL D'ALERTE (DERJA & FR)
    const btnPlayVoiceAlert = document.getElementById('btnPlayVoiceAlert');
    const audioVoiceAlert   = document.getElementById('audioVoiceAlert');
    const iconPlayVoice     = document.getElementById('iconPlayVoice');
    const voiceStatusText   = document.getElementById('voiceStatusText');
    const voiceTitleText    = document.getElementById('voiceTitleText');
    const langButtons       = document.querySelectorAll('.js-voice-lang');

    if (btnPlayVoiceAlert && audioVoiceAlert) {
      let currentLang = 'ar-TN';
      const zoneId = "{{ $userZone?->id ?? '' }}";

      function updateAudioSource(autoPlay = false) {
        if (!zoneId) return;
        const streamUrl = `/voice/stream/${zoneId}?lang=${currentLang}&t=${Date.now()}`;
        audioVoiceAlert.src = streamUrl;
        if (autoPlay) {
          playAudio();
        }
      }

      function playAudio() {
        if (!audioVoiceAlert.src) {
          updateAudioSource(false);
        }
        voiceStatusText.textContent = currentLang === 'ar-TN' ? 'قاعد يقرا في التنبيه الصوتي بالدارجة...' : 'Lecture du bulletin vocal en cours...';
        btnPlayVoiceAlert.classList.add('pulse-voice');
        audioVoiceAlert.play().then(() => {
          iconPlayVoice.className = 'bi bi-pause-fill fs-3';
        }).catch(err => {
          console.warn('Playback error or user blocked autoplay', err);
          voiceStatusText.textContent = 'Cliquez pour écouter.';
          iconPlayVoice.className = 'bi bi-play-fill fs-3';
          btnPlayVoiceAlert.classList.remove('pulse-voice');
        });
      }

      function pauseAudio() {
        audioVoiceAlert.pause();
        iconPlayVoice.className = 'bi bi-play-fill fs-3';
        btnPlayVoiceAlert.classList.remove('pulse-voice');
        voiceStatusText.textContent = 'En pause. Cliquez pour reprendre.';
      }

      btnPlayVoiceAlert.addEventListener('click', function() {
        if (audioVoiceAlert.paused || audioVoiceAlert.ended) {
          playAudio();
        } else {
          pauseAudio();
        }
      });

      audioVoiceAlert.addEventListener('ended', function() {
        iconPlayVoice.className = 'bi bi-arrow-repeat fs-3';
        btnPlayVoiceAlert.classList.remove('pulse-voice');
        voiceStatusText.textContent = currentLang === 'ar-TN' ? 'وفات القراءة. انقر لإعادة الاستماع.' : 'Lecture terminée. Cliquez pour réécouter.';
      });

      langButtons.forEach(btn => {
        btn.addEventListener('click', function() {
          langButtons.forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          currentLang = this.getAttribute('data-lang');

          if (currentLang === 'ar-TN') {
            voiceTitleText.textContent = '🎙️ Écouter le Bulletin Vocal en Derja';
            voiceStatusText.textContent = 'تنبيه صوتي بالدارجة التونسية للجهة متاعك.';
          } else {
            voiceTitleText.textContent = '🎙️ Écouter le Bulletin Vocal (FR)';
            voiceStatusText.textContent = 'Bulletin vocal préventif en français.';
          }

          updateAudioSource(!audioVoiceAlert.paused);
        });
      });

      // Initialisation
      updateAudioSource(false);
    }
  });
</script>
@endpush
@endsection
