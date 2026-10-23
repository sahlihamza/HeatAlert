@extends('layouts.front.front')

@section('content')
{{-- ═══════════════════════════════════════════════════════════════════════════
     DÉTECTION DES DÉPARTS DE FEUX EN TEMPS RÉEL (NASA FIRMS SATELLITE API)
═══════════════════════════════════════════════════════════════════════════ --}}

<!-- En-tête de page avec fond sombre et dégradé haute vigilance -->
<div class="page-title position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(127, 29, 29, 0.88) 100%), url('{{ asset('assets/front/img/health/emergency-1.webp') }}') center/cover no-repeat; padding-top: 135px; padding-bottom: 45px; border-bottom: 1px solid rgba(255,255,255,0.1);">
  <div class="heading">
    <div class="container">
      <div class="row d-flex justify-content-center text-center">
        <div class="col-lg-9">
          <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-3 rounded-pill bg-danger text-white small fw-bold shadow-sm">
            <span class="live-pulse-dot"></span>
            <i class="bi bi-fire"></i>
            <span>NASA FIRMS • Surveillance Thermique & Satellites NRT</span>
          </div>
          <h1 class="heading-title text-white" style="font-weight: 800; font-size: 38px; letter-spacing: -0.5px;">
            Détection des Départs de Feux & Points Chauds
          </h1>
          <p class="mb-0 text-white-50" style="font-size: 16px; max-width: 760px; margin: 0 auto; line-height: 1.6;">
            Surveillez en direct les anomalies thermiques et foyers d'incendies détectés par les satellites <strong class="text-white">NASA VIIRS & MODIS</strong> à proximité de vos zones et massifs forestiers.
          </p>
        </div>
      </div>
    </div>
  </div>
  <nav class="breadcrumbs mt-4" style="background: rgba(0, 0, 0, 0.3); backdrop-filter: blur(8px); border-top: 1px solid rgba(255,255,255,0.1);">
    <div class="container">
      <ol class="mb-0">
        <li><a href="{{ route('front.home') }}" class="text-white-50">Accueil</a></li>
        <li><a href="{{ route('front.alertes.index') }}" class="text-white-50">Vigilance & Santé</a></li>
        <li class="current text-white fw-semibold">Détection des Feux (NASA FIRMS)</li>
      </ol>
    </div>
  </nav>
</div>

<!-- Section Principale du Dashboard Feux -->
<section class="py-5" style="background-color: #f8fafc; min-height: 85vh;">
  <div class="container">

    <!-- ── BARRE DE CONTRÔLE & SÉLECTEUR DE ZONE ── -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 p-4" style="background: #ffffff;">
      <div class="row g-3 align-items-end">

        <!-- Sélection de la Zone HeatAlert -->
        <div class="col-lg-4 col-md-6">
          <label class="form-label small fw-bold text-dark mb-1">
            <i class="bi bi-geo-alt-fill text-danger me-1"></i>Zone de surveillance :
          </label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-danger"><i class="bi bi-pin-map-fill"></i></span>
            <select id="zoneSelect" class="form-select bg-light border-start-0 fw-semibold" onchange="onZoneChange()">
              <option value="custom">📍 Position personnalisée / GPS</option>
              @foreach($zones as $z)
                <option value="{{ $z->id }}"
                        data-lat="{{ $z->latitude }}"
                        data-lng="{{ $z->longitude }}"
                        data-name="{{ $z->nom }}"
                        data-gov="{{ $z->gouvernorat }}"
                        {{ (isset($selectedZone) && $selectedZone->id == $z->id) ? 'selected' : '' }}>
                  {{ $z->nom }} ({{ $z->gouvernorat }})
                </option>
              @endforeach
            </select>
          </div>
        </div>

        <!-- Rayon de Détection -->
        <div class="col-lg-3 col-md-6">
          <label class="form-label small fw-bold text-dark mb-1">
            <i class="bi bi-radar text-danger me-1"></i>Rayon de détection :
          </label>
          <div class="btn-group w-100 shadow-sm" role="group">
            <button type="button" class="btn btn-outline-secondary btn-sm radius-btn" data-radius="15" onclick="setRadius(15)">15 km</button>
            <button type="button" class="btn btn-outline-secondary btn-sm radius-btn active" data-radius="50" onclick="setRadius(50)">50 km</button>
            <button type="button" class="btn btn-outline-secondary btn-sm radius-btn" data-radius="100" onclick="setRadius(100)">100 km</button>
            <button type="button" class="btn btn-outline-secondary btn-sm radius-btn" data-radius="all" onclick="setRadius('all')">National</button>
          </div>
        </div>

        <!-- Période d'Acquisition Satellite -->
        <div class="col-lg-3 col-md-6">
          <label class="form-label small fw-bold text-dark mb-1">
            <i class="bi bi-clock-history text-danger me-1"></i>Période :
          </label>
          <div class="btn-group w-100 shadow-sm" role="group">
            <button type="button" class="btn btn-outline-secondary btn-sm days-btn active" data-days="1" onclick="setDays(1)">24h</button>
            <button type="button" class="btn btn-outline-secondary btn-sm days-btn" data-days="2" onclick="setDays(2)">48h</button>
            <button type="button" class="btn btn-outline-secondary btn-sm days-btn" data-days="3" onclick="setDays(3)">72h</button>
            <button type="button" class="btn btn-outline-secondary btn-sm days-btn" data-days="7" onclick="setDays(7)">7j</button>
          </div>
        </div>

        <!-- Actions Rapides & Impression Pro -->
        <div class="col-lg-2 col-md-6 d-flex gap-2">
          <button type="button" class="btn btn-outline-primary btn-sm flex-fill d-flex align-items-center justify-content-center gap-1 py-2 fw-semibold rounded-3 shadow-sm" onclick="locateUserPosition()" title="Surveiller les départs de feux autour de ma position GPS">
            <i class="bi bi-crosshair"></i>
            <span>GPS</span>
          </button>
          <button type="button" class="btn btn-danger btn-sm flex-fill d-flex align-items-center justify-content-center gap-1 py-2 fw-bold rounded-3 shadow-sm" onclick="refreshFiresData()" title="Actualiser le flux NASA">
            <i class="bi bi-arrow-clockwise"></i>
            <span>Actualiser</span>
          </button>
        </div>

      </div>

      <!-- Sous-barre : Satellite, Clé NASA et Impression Pro -->
      <div class="row g-2 align-items-center mt-3 pt-3 border-top">
        <div class="col-md-6 d-flex align-items-center flex-wrap gap-2">
          <span class="small fw-bold text-muted"><i class="bi bi-satellite text-danger me-1"></i>Instrument :</span>
          <select id="sourceSelect" class="form-select form-select-sm w-auto d-inline-block fw-semibold" onchange="onSourceChange()">
            @foreach($sources as $srcKey => $srcLabel)
              <option value="{{ $srcKey }}" {{ $srcKey === 'VIIRS_SNPP_NRT' ? 'selected' : '' }}>{{ $srcLabel }}</option>
            @endforeach
          </select>
          <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small d-inline-flex align-items-center gap-1">
            <i class="bi bi-check-circle-fill"></i> Clé NASA Active
          </span>
        </div>

        <div class="col-md-6 d-flex justify-content-md-end align-items-center gap-2 flex-wrap">
          <!-- Bouton Prédire la Propagation ML (LightGBM) -->
          <button type="button" class="btn btn-sm btn-warning text-dark rounded-pill px-3 py-1 fw-bold d-inline-flex align-items-center gap-2 shadow-sm" id="btnPredictSpread" onclick="runFireSpreadPrediction()" title="Prédire la propagation du feu dans les 24h avec le modèle LightGBM">
            <i class="bi bi-cpu-fill text-danger fs-6"></i>
            <span>Prédire la Propagation (ML 24h)</span>
          </button>
          <!-- Bouton Impression Pro -->
          <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm" onclick="printProReport()">
            <i class="bi bi-printer-fill text-danger"></i>
            <span>Impression Pro (Rapport)</span>
          </button>
          <!-- Bouton Export CSV -->
          <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm" onclick="exportFiresCsv()">
            <i class="bi bi-file-earmark-spreadsheet-fill"></i>
            <span>Export CSV</span>
          </button>
        </div>
      </div>

      <!-- Bandeau d'état du flux -->
      <div id="feedStatusBanner" class="alert alert-light border d-flex align-items-center gap-2 mt-3 mb-0 py-2 px-3 rounded-3 small">
        <i class="bi bi-info-circle-fill text-primary fs-6"></i>
        <div id="feedStatusText" class="flex-grow-1 text-secondary">
          Initialisation de la télémétrie satellite NASA FIRMS...
        </div>
        <span id="lastUpdatedBadge" class="badge bg-secondary-subtle text-dark fw-normal">--:--:--</span>
      </div>

    </div>

    <!-- ── STATISTIQUES & INDICATEURS PRO (KPIs) ── -->
    <div class="row g-3 mb-4">

      <!-- Total Foyers Détectés -->
      <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white stat-kpi-card">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-uppercase small fw-bold text-muted">Foyers Détectés</span>
            <div class="stat-icon-circle bg-danger-subtle text-danger">
              <i class="bi bi-fire fs-5"></i>
            </div>
          </div>
          <div class="d-flex align-items-baseline gap-2">
            <h2 id="kpiTotalFires" class="fs-1 fw-bolder text-danger mb-0">0</h2>
            <span class="small text-muted">hotspots actifs</span>
          </div>
          <div class="small text-muted mt-2 d-flex align-items-center gap-1">
            <i class="bi bi-shield-check text-success"></i>
            <span id="kpiMonitoringArea">Rayon : 50 km</span>
          </div>
        </div>
      </div>

      <!-- Foyers Haute Intensité -->
      <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white stat-kpi-card">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-uppercase small fw-bold text-muted">Haute Intensité (FRP > 10 MW)</span>
            <div class="stat-icon-circle bg-warning-subtle text-warning">
              <i class="bi bi-lightning-charge-fill fs-5"></i>
            </div>
          </div>
          <div class="d-flex align-items-baseline gap-2">
            <h2 id="kpiHighIntensity" class="fs-1 fw-bolder text-dark mb-0">0</h2>
            <span class="small text-muted">feux majeurs</span>
          </div>
          <div class="small text-muted mt-2 d-flex align-items-center gap-1">
            <i class="bi bi-speedometer2 text-danger"></i>
            <span>Max FRP : <strong id="kpiMaxFrp" class="text-danger">0 MW</strong></span>
          </div>
        </div>
      </div>

      <!-- Distance Foyer le Plus Proche -->
      <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white stat-kpi-card">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-uppercase small fw-bold text-muted">Plus Proche Foyer</span>
            <div class="stat-icon-circle bg-info-subtle text-info">
              <i class="bi bi-compass-fill fs-5"></i>
            </div>
          </div>
          <div class="d-flex align-items-baseline gap-2">
            <h2 id="kpiClosestDist" class="fs-1 fw-bolder text-primary mb-0">--</h2>
            <span class="small text-muted" id="kpiClosestUnit">km</span>
          </div>
          <div class="small text-muted mt-2 d-flex align-items-center gap-1">
            <i class="bi bi-geo-alt text-primary"></i>
            <span id="kpiClosestLoc">Par rapport au centre ciblé</span>
          </div>
        </div>
      </div>

      <!-- Niveau de Risque Global -->
      <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white stat-kpi-card">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-uppercase small fw-bold text-muted">Indice de Risque</span>
            <div class="stat-icon-circle bg-success-subtle text-success" id="kpiRiskIconCircle">
              <i class="bi bi-shield-fill-check fs-5" id="kpiRiskIcon"></i>
            </div>
          </div>
          <div class="d-flex align-items-baseline gap-2">
            <h4 id="kpiRiskStatus" class="fw-bold mb-0 text-success">Surveillance Normale</h4>
          </div>
          <div class="small text-muted mt-2 d-flex align-items-center gap-1">
            <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1" id="kpiRiskBadge">Sécurisé</span>
            <span id="kpiRiskAvgFrp">Moyenne: 0 MW</span>
          </div>
        </div>
      </div>

    </div>

    <!-- ── CARTE INTERACTIVE LEAFLET & LISTE DÉTAILLÉE ── -->
    <div class="row g-4">

      <!-- Colonne Carte (8 colonnes sur Desktop) -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">

          <!-- En-tête de carte avec contrôles de calque -->
          <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                <i class="bi bi-map-fill text-white" style="font-size: 13px;"></i>
              </span>
              <div>
                <h6 class="mb-0 fw-bold text-dark">Cartographie Satellite Interactive</h6>
                <small class="text-muted" id="mapTargetLabel">Zone : Tunis</small>
              </div>
            </div>

            <!-- Boutons de commande carte -->
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <!-- Switch satellite/plan -->
              <div class="btn-group btn-group-sm shadow-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active map-layer-btn" id="btnLayerSat" onclick="setMapLayer('satellite')">Satellite</button>
                <button type="button" class="btn btn-outline-secondary map-layer-btn" id="btnLayerOsm" onclick="setMapLayer('osm')">Plan</button>
                <button type="button" class="btn btn-outline-secondary map-layer-btn" id="btnLayerDark" onclick="setMapLayer('dark')">Sombre</button>
              </div>

              <!-- Bouton Calque Propagation ML -->
              <button type="button" class="btn btn-sm btn-outline-danger shadow-sm fw-bold d-inline-flex align-items-center gap-1 active" id="btnToggleSpreadLayer" onclick="toggleSpreadLayer()" title="Afficher/Masquer le calque prédictif de propagation ML">
                <i class="bi bi-radar"></i>
                <span class="d-none d-sm-inline">Calque ML</span>
              </button>

              <!-- Switch Rayon cercle -->
              <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm" id="btnToggleCircle" onclick="toggleRadiusCircle()" title="Afficher/Masquer le cercle de rayon">
                <i class="bi bi-circle"></i>
              </button>

              <!-- Recentrer -->
              <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm" onclick="recenterMap()" title="Recentrer sur la zone">
                <i class="bi bi-arrows-fullscreen"></i>
              </button>
            </div>
          </div>

          <!-- Conteneur Carte Leaflet -->
          <div class="position-relative">
            <div id="firmsMap" style="height: 540px; width: 100%; background: #0f172a;"></div>

            <!-- Légende Flottante sur la carte -->
            <div class="map-legend-overlay p-2 rounded-3 shadow-sm">
              <div class="fw-bold small mb-1 text-dark">Intensité FRP (MW) :</div>
              <div class="d-flex align-items-center gap-2 small">
                <span class="legend-dot" style="background: #22c55e;"></span> &lt; 5 MW (Faible)
              </div>
              <div class="d-flex align-items-center gap-2 small">
                <span class="legend-dot" style="background: #f59e0b;"></span> 5 - 15 MW (Modéré)
              </div>
              <div class="d-flex align-items-center gap-2 small">
                <span class="legend-dot" style="background: #ea580c;"></span> 15 - 50 MW (Élevé)
              </div>
              <div class="d-flex align-items-center gap-2 small">
                <span class="legend-dot" style="background: #dc2626;"></span> &gt; 50 MW (Critique)
              </div>
              <div class="fw-bold small mb-1 text-dark border-top pt-2 mt-2">Propagation ML (24h) :</div>
              <div class="d-flex align-items-center gap-2 small">
                <span class="legend-dot" style="background: #dc2626; box-shadow: 0 0 0 2px rgba(220,38,38,0.4);"></span> &ge; 70% (Critique)
              </div>
              <div class="d-flex align-items-center gap-2 small">
                <span class="legend-dot" style="background: #ea580c;"></span> 45 - 70% (Élevé)
              </div>
              <div class="d-flex align-items-center gap-2 small">
                <span class="legend-dot" style="background: #f59e0b;"></span> 25 - 45% (Modéré)
              </div>
              <div class="d-flex align-items-center gap-2 small">
                <span class="legend-dot" style="background: #10b981;"></span> &lt; 25% (Faible)
              </div>
            </div>

            <!-- Spinner de chargement carte -->
            <div id="mapLoadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-none d-flex align-items-center justify-content-center" style="background: rgba(15,23,42,0.65); backdrop-filter: blur(4px); z-index: 1000;">
              <div class="text-center text-white">
                <div class="spinner-border text-danger" style="width: 3rem; height: 3rem;" role="status"></div>
                <div class="mt-2 fw-semibold">Interrogation des satellites NASA...</div>
                <div class="small text-white-50">Téléchargement des données NRT en cours</div>
              </div>
            </div>
          </div>

          <!-- Barre d'infos sous la carte -->
          <div class="p-3 bg-light border-top d-flex align-items-center justify-content-between flex-wrap gap-2 small text-muted">
            <div class="d-flex align-items-center gap-3">
              <span><i class="bi bi-info-circle me-1 text-primary"></i>Cliquez sur un foyer pour voir sa puissance radiative et ses coordonnées précises.</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-white text-dark border">NASA FIRMS API</span>
              <span class="badge bg-white text-dark border">EOSDIS LANCE</span>
            </div>
          </div>

        </div>
      </div>

      <!-- Colonne Droite : Liste des Foyers & Urgence (4 colonnes) -->
      <div class="col-lg-4">

        <!-- Carte Urgence & Consignes Immédiates -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 text-white overflow-hidden" style="background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%);">
          <div class="p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="badge bg-white text-danger fw-bold text-uppercase px-2 py-1">Protection Civile</span>
              <i class="bi bi-telephone-outbound-fill fs-5 text-white-50"></i>
            </div>
            <h5 class="fw-bold mb-1">Alerte Incendie de Forêt ?</h5>
            <p class="small text-white-75 mb-3" style="font-size: 13px;">
              En cas de détection visuelle de fumée suspecte ou départ de feu, alertez immédiatement les secours.
            </p>
            <div class="d-grid gap-2">
              <a href="tel:198" class="btn btn-white text-danger fw-bold rounded-pill shadow-sm d-flex align-items-center justify-content-center gap-2 py-2" style="background: #ffffff;">
                <i class="bi bi-telephone-fill"></i>
                <span>Appeler la Protection Civile (198)</span>
              </a>
              <a href="tel:190" class="btn btn-outline-light rounded-pill btn-sm d-flex align-items-center justify-content-center gap-1">
                <span>SAMU Médical : 190</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Liste des Foyers Détectés -->
        <div class="card border-0 shadow-sm rounded-4 bg-white h-auto overflow-hidden">
          <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
            <div class="fw-bold text-dark d-flex align-items-center gap-2">
              <i class="bi bi-list-ul text-danger"></i>
              <span>Foyers Détectés</span>
            </div>
            <span id="hotspotsCountBadge" class="badge bg-danger rounded-pill">0</span>
          </div>

          <div id="hotspotsListContainer" class="p-3" style="max-height: 480px; overflow-y: auto;">
            <!-- Message par défaut / vide -->
            <div id="emptyHotspotsState" class="text-center py-4 text-muted">
              <i class="bi bi-shield-check text-success fs-1 mb-2 d-block"></i>
              <h6 class="fw-bold text-dark">Aucun départ de feu actif</h6>
              <p class="small text-muted mb-0">Les capteurs satellites n'ont relevé aucune anomalie thermique critique dans ce périmètre.</p>
            </div>
            <!-- Liste injectée en JavaScript -->
            <div id="hotspotsList" class="d-none"></div>
          </div>
        </div>

      </div>

    </div>

    <!-- ── GUIDE DE PRÉVENTION & CONSIGNES VIGILANCE SANTÉ ── -->
    <div class="row g-4 mt-2">
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
          <div class="row g-4 align-items-center">
            <div class="col-lg-8">
              <div class="d-flex align-items-center gap-2 text-danger mb-2">
                <i class="bi bi-shield-exclamation fs-4"></i>
                <h5 class="mb-0 fw-bold text-dark">Consignes de Vigilance & Santé en Cas de Fumées / Feux</h5>
              </div>
              <p class="text-muted small mb-3">
                L'inhalation de fumées d'incendies de forêt aggrave drastiquement les risques respiratoires et cardiovasculaires, en particulier lors des épisodes de canicule intense.
              </p>
              <div class="row g-3">
                <div class="col-md-4">
                  <div class="p-3 rounded-3 bg-light border h-100">
                    <div class="fw-bold text-dark small mb-1"><i class="bi bi-house-door-fill text-primary me-1"></i>Calfeutrer le domicile</div>
                    <div class="text-muted small">Fermez portes, fenêtres et volets. Coupez la ventilation extérieure pour limiter la pénétration des particules fines.</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="p-3 rounded-3 bg-light border h-100">
                    <div class="fw-bold text-dark small mb-1"><i class="bi bi-mask text-warning me-1"></i>Protection respiratoire</div>
                    <div class="text-muted small">Personnes vulnérables (asthmatiques, personnes âgées, enfants) : portez un masque FFP2 en cas d'odeur de fumée.</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="p-3 rounded-3 bg-light border h-100">
                    <div class="fw-bold text-dark small mb-1"><i class="bi bi-cup-straw text-info me-1"></i>Hydratation renforcée</div>
                    <div class="text-muted small">Buvez de l'eau régulièrement pour apaiser les voies respiratoires asséchées par la chaleur et les microparticules.</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-4 text-center border-start-lg ps-lg-4">
              <div class="p-3 rounded-4 bg-danger-subtle text-danger mb-3">
                <div class="fs-4 fw-bold mb-1">Veille Canicule & Feux</div>
                <div class="small">Synchronisation en continu avec la Protection Civile et l'Institut National de la Météorologie.</div>
              </div>
              <a href="{{ route('front.conseils.index') }}" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-2 fw-semibold w-100">
                <i class="bi bi-heart-pulse-fill me-1"></i>Voir tous les Conseils Santé Canicule
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         SECTION PRÉDICTIVE ML : PROPAGATION DU FEU (LIGHTGBM 24H)
         ══════════════════════════════════════════════════════════ -->
    <div id="mlSpreadDashboardSection" class="row g-4 mt-2 d-none">
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
          <div class="card-header bg-gradient p-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-danger rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                <i class="bi bi-cpu-fill text-white fs-6"></i>
              </span>
              <div>
                <h5 class="mb-0 fw-bold">Modèle Prédictif ML : Risque de Propagation dans les 24h</h5>
                <small class="text-white-50">Algorithme LightGBM calibré sur le climat estival & vent dominant de Tunisie • Modèle : <span id="mlModelVersionBadge">lightgbm_fire_spread:v1</span></small>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill shadow-sm">
                <i class="bi bi-lightning-charge-fill me-1"></i>Prédiction 24h Active
              </span>
            </div>
          </div>

          <div class="card-body p-4">
            <!-- 4 KPIs Prédictifs -->
            <div class="row g-3 mb-4">
              <div class="col-xl-3 col-md-6">
                <div class="p-3 rounded-4 border bg-danger-subtle border-danger-subtle h-100">
                  <div class="small fw-bold text-danger text-uppercase mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i>Cellules Risque Critique</div>
                  <h3 id="mlKpiCritique" class="fw-bolder text-danger mb-0">0</h3>
                  <small class="text-muted">Probabilité &ge; 70% (front actif)</small>
                </div>
              </div>
              <div class="col-xl-3 col-md-6">
                <div class="p-3 rounded-4 border bg-warning-subtle border-warning-subtle h-100">
                  <div class="small fw-bold text-warning-emphasis text-uppercase mb-1"><i class="bi bi-cone-striped me-1"></i>Cellules Risque Élevé</div>
                  <h3 id="mlKpiEleve" class="fw-bolder text-dark mb-0">0</h3>
                  <small class="text-muted">Probabilité entre 45% et 70%</small>
                </div>
              </div>
              <div class="col-xl-3 col-md-6">
                <div class="p-3 rounded-4 border bg-light h-100">
                  <div class="small fw-bold text-secondary text-uppercase mb-1"><i class="bi bi-arrows-angle-expand me-1"></i>Surface Menacée Estimée</div>
                  <h3 id="mlKpiArea" class="fw-bolder text-dark mb-0">0 km²</h3>
                  <small class="text-muted">Rayon de propagation sous surveillance</small>
                </div>
              </div>
              <div class="col-xl-3 col-md-6">
                <div class="p-3 rounded-4 border bg-primary-subtle border-primary-subtle h-100">
                  <div class="small fw-bold text-primary text-uppercase mb-1"><i class="bi bi-shield-shaded me-1"></i>Zones / Communes en Alerte</div>
                  <h3 id="mlKpiThreatenedCount" class="fw-bolder text-primary mb-0">0</h3>
                  <small class="text-muted">Localités à proximité directe du front</small>
                </div>
              </div>
            </div>

            <!-- Graphiques Chart.js & Facteurs Explicatifs -->
            <div class="row g-4 mb-4">
              <!-- Graphique 1 : Distribution des Niveaux de Risque -->
              <div class="col-lg-5">
                <div class="p-3 rounded-4 border bg-light h-100">
                  <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart-fill text-danger"></i>
                    <span>Répartition des Cellules par Risque</span>
                  </h6>
                  <div style="height: 240px; position: relative;">
                    <canvas id="chartSpreadDistribution"></canvas>
                  </div>
                </div>
              </div>

              <!-- Graphique 2 : Facteurs Météo & Risque de Propagation -->
              <div class="col-lg-7">
                <div class="p-3 rounded-4 border bg-light h-100">
                  <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-fill text-warning"></i>
                    <span>Facteurs Clés Influant la Propagation (Poids ML)</span>
                  </h6>
                  <div style="height: 240px; position: relative;">
                    <canvas id="chartSpreadFactors"></canvas>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tableau des Zones / Communes Menacées Prioritaires -->
            <div class="border rounded-4 overflow-hidden">
              <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                  <i class="bi bi-buildings-fill text-danger"></i>
                  <span>Zones Habitées & Massifs Forestiers Menacés dans les 24h</span>
                </h6>
                <span class="badge bg-danger rounded-pill" id="mlBadgeZonesCount">0 zone(s)</span>
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="mlThreatenedZonesTable">
                  <thead class="table-light small text-uppercase text-muted">
                    <tr>
                      <th class="ps-3">Zone / Commune</th>
                      <th>Gouvernorat</th>
                      <th>Distance au Front</th>
                      <th>Probabilité Max</th>
                      <th>Niveau de Risque</th>
                      <th>Recommandation Protection Civile</th>
                    </tr>
                  </thead>
                  <tbody id="mlThreatenedZonesBody">
                    <tr>
                      <td colspan="6" class="text-center py-4 text-muted">
                        Cliquez sur "Prédire la Propagation (ML 24h)" pour lancer le diagnostic spatial.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Toast HeatAlert (Sans alert() du navigateur) -->
    <div id="fireToastWrapper" class="position-fixed top-0 end-0 p-3" style="z-index: 999999; margin-top: 75px;">
      <div id="fireToastNotification" class="toast align-items-center text-white border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="display: none; min-width: 320px; max-width: 440px; border-radius: 16px; background: rgba(15, 23, 42, 0.96); backdrop-filter: blur(12px); border-left: 5px solid #f59e0b;">
        <div class="d-flex p-3 align-items-center">
          <div id="fireToastIcon" class="me-3 fs-3 text-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <div class="toast-body p-0 flex-grow-1" style="font-size: 13.5px; font-weight: 500; line-height: 1.45;" id="fireToastMessage">
            Aucun foyer actif détecté pour calculer la propagation.
          </div>
          <button type="button" class="btn-close btn-close-white ms-2 me-0" onclick="hideFireToast()" aria-label="Fermer"></button>
        </div>
      </div>
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════════════════════════════════════
     FICHE OFFICIELLE D'IMPRESSION PRO (Visible uniquement lors du print)
═══════════════════════════════════════════════════════════════════════════ -->
<div id="printProReportContainer" class="d-none">
  <div class="print-header">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #dc2626; padding-bottom: 12px; margin-bottom: 20px;">
      <div>
        <h2 style="color: #1e3a5f; margin: 0; font-weight: 800; font-size: 24px;">Heat<span style="color: #dc2626;">Alert</span></h2>
        <div style="font-size: 13px; color: #64748b; font-weight: 600;">Plateforme Nationale de Vigilance Canicule & Feux de Forêt</div>
      </div>
      <div style="text-align: right;">
        <span style="background: #fee2e2; color: #dc2626; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; display: inline-block;">
          BULLETIN OFFICIEL DE SURVEILLANCE SATELLITE
        </span>
        <div style="font-size: 11px; color: #475569; margin-top: 4px;" id="printReportTimestamp">Date d'édition : --</div>
      </div>
    </div>
  </div>

  <div class="print-meta" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 20px;">
    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
      <tr>
        <td style="padding: 4px; font-weight: bold; width: 25%;">Zone Ciblée :</td>
        <td style="padding: 4px;" id="printZoneName">--</td>
        <td style="padding: 4px; font-weight: bold; width: 25%;">Source Télémétrique :</td>
        <td style="padding: 4px;" id="printSourceSensor">NASA FIRMS (VIIRS/MODIS)</td>
      </tr>
      <tr>
        <td style="padding: 4px; font-weight: bold;">Coordonnées Centre :</td>
        <td style="padding: 4px;" id="printCoordinates">--</td>
        <td style="padding: 4px; font-weight: bold;">Période Analysée :</td>
        <td style="padding: 4px;" id="printTimeRange">Dernières 24 heures</td>
      </tr>
      <tr>
        <td style="padding: 4px; font-weight: bold;">Rayon de Surveillance :</td>
        <td style="padding: 4px;" id="printRadius">50 km</td>
        <td style="padding: 4px; font-weight: bold;">Statut Risque Incendie :</td>
        <td style="padding: 4px; font-weight: bold; color: #dc2626;" id="printRiskStatus">--</td>
      </tr>
    </table>
  </div>

  <div style="display: flex; gap: 15px; margin-bottom: 20px;">
    <div style="flex: 1; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; text-align: center;">
      <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Total Foyers</div>
      <div style="font-size: 20px; font-weight: 800; color: #dc2626;" id="printTotalFires">0</div>
    </div>
    <div style="flex: 1; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; text-align: center;">
      <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">FRP Maximum (MW)</div>
      <div style="font-size: 20px; font-weight: 800; color: #ea580c;" id="printMaxFrp">0 MW</div>
    </div>
    <div style="flex: 1; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px; text-align: center;">
      <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Distance Plus Proche</div>
      <div style="font-size: 20px; font-weight: 800; color: #0284c7;" id="printClosestDist">--</div>
    </div>
  </div>

  <div style="margin-bottom: 25px;">
    <h4 style="font-size: 14px; font-weight: bold; margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">
      Relevé Détaillé des Détections Satellitaires
    </h4>
    <table style="width: 100%; border-collapse: collapse; font-size: 11px;" id="printHotspotsTable">
      <thead>
        <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1; text-align: left;">
          <th style="padding: 6px; border: 1px solid #cbd5e1;">#</th>
          <th style="padding: 6px; border: 1px solid #cbd5e1;">Latitude / Longitude</th>
          <th style="padding: 6px; border: 1px solid #cbd5e1;">Localisation / Zone</th>
          <th style="padding: 6px; border: 1px solid #cbd5e1;">Énergie FRP</th>
          <th style="padding: 6px; border: 1px solid #cbd5e1;">Brillance (K)</th>
          <th style="padding: 6px; border: 1px solid #cbd5e1;">Distance</th>
          <th style="padding: 6px; border: 1px solid #cbd5e1;">Date & Heure UTC</th>
          <th style="padding: 6px; border: 1px solid #cbd5e1;">Niveau Confiance</th>
        </tr>
      </thead>
      <tbody id="printHotspotsTableBody">
        <!-- Rempli dynamiquement -->
      </tbody>
    </table>
  </div>

  <div style="margin-top: 30px; border-top: 1px solid #cbd5e1; padding-top: 15px; font-size: 11px; color: #475569; display: flex; justify-content: space-between;">
    <div>
      <strong>Urgence Incendie :</strong> Protection Civile (198) • SAMU (190) • Garde Nationale Forestière<br>
      Données certifiées issues de la constellation satellitaire NASA EOSDIS / LANCE FIRMS.
    </div>
    <div style="text-align: right; width: 220px;">
      <div style="border-bottom: 1px dashed #94a3b8; height: 40px; margin-bottom: 4px;"></div>
      <em>Visa & Signature Responsable Sécurité</em>
    </div>
  </div>
</div>

@endsection

@push('styles')
{{-- Feuille de style Leaflet --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
  /* Points pulsants pour le live */
  .live-pulse-dot {
    width: 8px;
    height: 8px;
    background-color: #22c55e;
    border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
    animation: livePulse 1.8s infinite;
  }
  @keyframes livePulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
  }

  /* Cartes KPIs */
  .stat-kpi-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #e2e8f0;
  }
  .stat-kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.06) !important;
  }
  .stat-icon-circle {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* Légende Carte Flottante */
  .map-legend-overlay {
    position: absolute;
    bottom: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(0,0,0,0.1);
    z-index: 990;
    max-width: 220px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
  }
  .legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
  }

  /* Marqueurs Flammes Personnalisés sur la carte */
  .fire-marker-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: pointer;
  }
  .fire-marker-pulse {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    animation: firePulse 1.6s ease-out infinite;
    pointer-events: none;
  }
  .fire-marker-core {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 13px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.4);
    z-index: 2;
    transition: transform 0.2s ease;
  }
  .fire-marker-core:hover {
    transform: scale(1.25);
  }
  @keyframes firePulse {
    0% { transform: scale(0.6); opacity: 0.9; }
    100% { transform: scale(2.2); opacity: 0; }
  }

  /* Item de la liste des foyers */
  .hotspot-list-item {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
    cursor: pointer;
  }
  .hotspot-list-item:hover {
    background-color: #f8fafc;
    border-left-color: #dc2626;
    transform: translateX(3px);
  }

  /* Styles d'Impression Pro */
  @media print {
    body * {
      visibility: hidden;
    }
    #printProReportContainer, #printProReportContainer * {
      visibility: visible;
    }
    #printProReportContainer {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      display: block !important;
      background: #ffffff !important;
      padding: 15mm 20mm;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: #0f172a;
    }
    .header, .footer, #header, #footer, nav, .btn, .page-title, .breadcrumbs {
      display: none !important;
    }
  }
  /* Toast Notification Animation */
  .toast-slide-animate {
    animation: toastSlideDown 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }
  @keyframes toastSlideDown {
    from {
      opacity: 0;
      transform: translateY(-20px) scale(0.96);
    }
    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }
</style>
@endpush

@push('scripts')
{{-- Leaflet.js & Chart.js --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
(function() {
  'use strict';

  // ─── État Global du Dashboard ───
  let currentLat = {{ $selectedZone ? (float)$selectedZone->latitude : 36.8065 }};
  let currentLng = {{ $selectedZone ? (float)$selectedZone->longitude : 10.1815 }};
  let currentZoneName = "{{ $selectedZone ? addslashes($selectedZone->nom) : 'Tunis' }}";
  let currentZoneId = "{{ $selectedZone ? $selectedZone->id : 'custom' }}";
  let currentRadius = 50; // km ou 'all'
  let currentDays = 1;
  let currentSource = 'VIIRS_SNPP_NRT';

  let map = null;
  let mapTileLayer = null;
  let markersLayerGroup = null;
  let spreadLayerGroup = null;
  let radiusCircle = null;
  let centerMarker = null;

  let cachedHotspots = [];
  let currentTileType = 'satellite';
  let chartDistribution = null;
  let chartFactors = null;
  let lastSpreadData = null;

  // Couches de tuiles
  const TILE_PROVIDERS = {
    satellite: {
      url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
      options: { attribution: 'Tiles &copy; Esri &mdash; NASA FIRMS Active Fires' }
    },
    osm: {
      url: 'https://{s}.tile.openstreetmap.org/{z}/{y}/{x}.png',
      options: { attribution: '&copy; OpenStreetMap contributors' }
    },
    dark: {
      url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
      options: { attribution: '&copy; CartoDB & OpenStreetMap' }
    }
  };

  // ─── Initialisation ───
  document.addEventListener('DOMContentLoaded', function() {
    initMap();
    fetchFires();
  });

  // Initialisation de la carte Leaflet
  function initMap() {
    const mapContainer = document.getElementById('firmsMap');
    if (!mapContainer || typeof L === 'undefined') return;

    map = L.map('firmsMap', {
      center: [currentLat, currentLng],
      zoom: 9,
      zoomControl: true,
      maxZoom: 18,
      minZoom: 4
    });

    // Tuile par défaut : Satellite
    setMapLayer('satellite');

    // Groupe de calques pour les feux
    markersLayerGroup = L.layerGroup().addTo(map);

    // Groupe de calques pour la propagation ML LightGBM
    spreadLayerGroup = L.layerGroup().addTo(map);

    // Dessin du rayon initial
    updateCenterRadiusOverlay();

    // Clic sur la carte pour définir un point personnalisé
    map.on('click', function(e) {
      currentLat = parseFloat(e.latlng.lat.toFixed(4));
      currentLng = parseFloat(e.latlng.lng.toFixed(4));
      currentZoneName = `Coordonnées (${currentLat.toFixed(2)}, ${currentLng.toFixed(2)})`;
      currentZoneId = 'custom';

      const select = document.getElementById('zoneSelect');
      if (select) select.value = 'custom';

      updateCenterRadiusOverlay();
      fetchFires();
    });
  }

  // Changement de calque de fond (Satellite / Plan / Sombre)
  window.setMapLayer = function(type) {
    if (!map || !TILE_PROVIDERS[type]) return;
    currentTileType = type;

    if (mapTileLayer) {
      map.removeLayer(mapTileLayer);
    }

    mapTileLayer = L.tileLayer(TILE_PROVIDERS[type].url, TILE_PROVIDERS[type].options).addTo(map);

    // Mise à jour de l'apparence des boutons
    document.querySelectorAll('.map-layer-btn').forEach(btn => btn.classList.remove('active'));
    if (type === 'satellite') document.getElementById('btnLayerSat')?.classList.add('active');
    if (type === 'osm') document.getElementById('btnLayerOsm')?.classList.add('active');
    if (type === 'dark') document.getElementById('btnLayerDark')?.classList.add('active');
  };

  // Met à jour le cercle de rayon et le marqueur central
  function updateCenterRadiusOverlay() {
    if (!map) return;

    // Suppression anciens overlays de rayon
    if (radiusCircle) {
      map.removeLayer(radiusCircle);
      radiusCircle = null;
    }
    if (centerMarker) {
      map.removeLayer(centerMarker);
      centerMarker = null;
    }

    if (currentRadius !== 'all' && isFinite(currentRadius)) {
      radiusCircle = L.circle([currentLat, currentLng], {
        radius: currentRadius * 1000,
        color: '#dc2626',
        fillColor: '#dc2626',
        fillOpacity: 0.08,
        weight: 1.5,
        dashArray: '4, 4'
      }).addTo(map);
    }

    // Marqueur de la zone de référence
    const centerIcon = L.divIcon({
      className: 'center-pin-icon',
      html: `
        <div style="background: #1e3a5f; color: #fff; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.5);">
          <i class="bi bi-geo-alt-fill" style="font-size: 14px;"></i>
        </div>
      `,
      iconSize: [30, 30],
      iconAnchor: [15, 15]
    });

    centerMarker = L.marker([currentLat, currentLng], { icon: centerIcon })
      .bindPopup(`<strong>Point de référence :</strong><br>${currentZoneName}`)
      .addTo(map);

    document.getElementById('mapTargetLabel').textContent = `Centre : ${currentZoneName} • Rayon : ${currentRadius === 'all' ? 'National' : currentRadius + ' km'}`;
  }

  // ─── Récupération des données satellites via l'API ───
  window.fetchFires = function() {
    const loadingOverlay = document.getElementById('mapLoadingOverlay');
    if (loadingOverlay) loadingOverlay.classList.remove('d-none');

    const params = new URLSearchParams({
      lat: currentLat,
      lng: currentLng,
      radius: currentRadius,
      days: currentDays,
      source: currentSource
    });

    if (currentZoneId && currentZoneId !== 'custom') {
      params.append('zone_id', currentZoneId);
    }

    fetch(`{{ route('front.fires.api') }}?${params.toString()}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(res => res.json())
    .then(data => {
      if (loadingOverlay) loadingOverlay.classList.add('d-none');
      renderFiresData(data);
    })
    .catch(err => {
      console.error('Erreur API NASA FIRMS :', err);
      if (loadingOverlay) loadingOverlay.classList.add('d-none');
      document.getElementById('feedStatusText').innerHTML = `
        <span class="text-danger fw-semibold">Impossible de contacter le service NASA FIRMS. Veuillez vérifier votre connexion ou réessayer.</span>
      `;
    });
  };

  // Rendu des données reçues sur la carte et le dashboard
  function renderFiresData(data) {
    cachedHotspots = data.hotspots || [];

    // 1. Mise à jour de la bannière d'état
    const feedStatus = document.getElementById('feedStatusText');
    const badge = document.getElementById('lastUpdatedBadge');
    if (feedStatus) {
      const isFallback = data.is_fallback;
      const statusIcon = isFallback ? '<i class="bi bi-shield-check text-warning me-1"></i>' : '<i class="bi bi-broadcast text-success me-1"></i>';
      feedStatus.innerHTML = `${statusIcon} ${data.message || 'Données satellites actualisées.'}`;
    }
    if (badge) {
      const now = new Date();
      badge.textContent = `Actualisé à ${now.toLocaleTimeString()}`;
    }

    // 2. Mise à jour des KPIs
    const stats = data.stats || {};
    document.getElementById('kpiTotalFires').textContent = stats.total || 0;
    document.getElementById('kpiHighIntensity').textContent = stats.high_intensity || 0;
    document.getElementById('kpiMaxFrp').textContent = `${stats.max_frp || 0} MW`;

    const closestElem = document.getElementById('kpiClosestDist');
    if (stats.closest_dist_km !== null && stats.closest_dist_km !== undefined) {
      closestElem.textContent = stats.closest_dist_km;
      document.getElementById('kpiClosestUnit').textContent = 'km';
    } else {
      closestElem.textContent = '--';
      document.getElementById('kpiClosestUnit').textContent = 'aucun foyer';
    }

    const riskStatusElem = document.getElementById('kpiRiskStatus');
    const riskBadgeElem = document.getElementById('kpiRiskBadge');
    const riskAvgElem = document.getElementById('kpiRiskAvgFrp');

    if (riskStatusElem) riskStatusElem.textContent = stats.risk_status || 'Normale';
    if (riskBadgeElem) {
      riskBadgeElem.className = `badge bg-${stats.risk_badge || 'success'}-subtle text-${stats.risk_badge || 'success'} rounded-pill px-2 py-1`;
      riskBadgeElem.textContent = stats.risk_badge === 'danger' ? 'Critique' : (stats.risk_badge === 'warning' ? 'Vigilance' : 'Normal');
    }
    if (riskAvgElem) riskAvgElem.textContent = `Moyenne: ${stats.avg_frp || 0} MW`;

    document.getElementById('kpiMonitoringArea').textContent = `Rayon : ${currentRadius === 'all' ? 'Toute la Tunisie' : currentRadius + ' km'}`;

    // 3. Dessin des marqueurs feux sur la carte
    markersLayerGroup.clearLayers();

    if (cachedHotspots.length > 0) {
      const bounds = L.latLngBounds();
      bounds.extend([currentLat, currentLng]);

      cachedHotspots.forEach((h, index) => {
        const color = getFrpColor(h.frp_mw);
        const marker = createFireMarker(h, color, index);
        marker.addTo(markersLayerGroup);
        bounds.extend([h.latitude, h.longitude]);
      });

      // Recentrer la carte avec marge douce
      if (currentRadius === 'all') {
        map.fitBounds(bounds, { padding: [40, 40] });
      }
    }

    // 4. Mise à jour de la liste latérale
    renderHotspotsList(cachedHotspots);
  }

  // Crée un marqueur de flamme personnalisé avec effet pulsant
  function createFireMarker(hotspot, color, index) {
    const iconHtml = `
      <div class="fire-marker-icon" style="width: 32px; height: 32px;">
        <div class="fire-marker-pulse" style="background-color: ${color};"></div>
        <div class="fire-marker-core" style="background-color: ${color};">
          <i class="bi bi-fire"></i>
        </div>
      </div>
    `;

    const icon = L.divIcon({
      className: 'custom-fire-div-icon',
      html: iconHtml,
      iconSize: [32, 32],
      iconAnchor: [16, 16],
      popupAnchor: [0, -18]
    });

    const marker = L.marker([hotspot.latitude, hotspot.longitude], { icon: icon });

    const localityInfo = hotspot.locality ? `<div class="fw-bold mb-1">${hotspot.locality} (${hotspot.gouvernorat || ''})</div>` : '';
    const distInfo = hotspot.distance_km !== null ? `<tr><td>Distance du centre :</td><td><strong>${hotspot.distance_km} km</strong></td></tr>` : '';

    const popupHtml = `
      <div style="font-size: 13px; font-family: inherit; min-width: 220px;">
        <div class="d-flex align-items-center gap-1 text-danger fw-bold mb-1" style="font-size: 14px;">
          <i class="bi bi-fire"></i> Foyer Actif #${index + 1}
        </div>
        ${localityInfo}
        <table class="table table-sm table-borderless my-2" style="font-size: 12px; margin-bottom: 6px;">
          <tr><td>Puissance FRP :</td><td><strong style="color: ${color};">${hotspot.frp_mw} MW</strong></td></tr>
          <tr><td>Temp. Brillance :</td><td><strong>${hotspot.brightness_k} K</strong></td></tr>
          <tr><td>Niveau Confiance :</td><td><span class="badge bg-secondary-subtle text-dark">${hotspot.confidence}</span></td></tr>
          <tr><td>Satellite :</td><td>${hotspot.satellite}</td></tr>
          <tr><td>Passage :</td><td>${hotspot.acq_date} à ${hotspot.acq_time} UTC (${hotspot.daynight})</td></tr>
          ${distInfo}
          <tr><td>Coordonnées :</td><td><code>${hotspot.latitude.toFixed(4)}, ${hotspot.longitude.toFixed(4)}</code></td></tr>
        </table>
        <div class="d-grid mt-2">
          <a href="tel:198" class="btn btn-danger btn-sm text-white fw-bold py-1" style="font-size: 11px;">
            <i class="bi bi-telephone-fill me-1"></i>Alerter Secours (198)
          </a>
        </div>
      </div>
    `;

    marker.bindPopup(popupHtml);
    return marker;
  }

  // Rendu de la liste latérale
  function renderHotspotsList(hotspots) {
    const countBadge = document.getElementById('hotspotsCountBadge');
    const emptyState = document.getElementById('emptyHotspotsState');
    const listElem = document.getElementById('hotspotsList');

    if (countBadge) countBadge.textContent = hotspots.length;

    if (hotspots.length === 0) {
      emptyState.classList.remove('d-none');
      listElem.classList.add('d-none');
      listElem.innerHTML = '';
      return;
    }

    emptyState.classList.add('d-none');
    listElem.classList.remove('d-none');

    let html = '';
    hotspots.forEach((h, idx) => {
      const color = getFrpColor(h.frp_mw);
      const locality = h.locality || `Anomalie thermique lat ${h.latitude.toFixed(2)}`;
      const dist = h.distance_km !== null ? `<span class="badge bg-light text-dark border"><i class="bi bi-compass me-1"></i>${h.distance_km} km</span>` : '';

      html += `
        <div class="hotspot-list-item p-3 mb-2 rounded-3 bg-white border shadow-sm" onclick="zoomToHotspot(${h.latitude}, ${h.longitude})">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="fw-bold text-dark small">
              <i class="bi bi-fire me-1" style="color: ${color};"></i>#${idx + 1} ${locality}
            </span>
            <span class="badge" style="background-color: ${color}; color: #fff; font-size: 10px;">
              ${h.frp_mw} MW
            </span>
          </div>
          <div class="d-flex align-items-center justify-content-between text-muted small mt-2" style="font-size: 11px;">
            <span><i class="bi bi-clock me-1"></i>${h.acq_date} ${h.acq_time} UTC</span>
            ${dist}
          </div>
          <div class="mt-2 d-flex align-items-center justify-content-between small" style="font-size: 11px;">
            <span class="text-secondary">${h.satellite}</span>
            <a href="javascript:void(0)" class="text-danger fw-semibold text-decoration-none">
              <i class="bi bi-geo-alt me-1"></i>Centrer
            </a>
          </div>
        </div>
      `;
    });

    listElem.innerHTML = html;
  }

  // Zoomer vers un foyer spécifique
  window.zoomToHotspot = function(lat, lng) {
    if (!map) return;
    map.setView([lat, lng], 13, { animate: true });

    // Ouvrir le popup correspondant
    markersLayerGroup.eachLayer(layer => {
      if (layer.getLatLng && layer.getLatLng().lat === lat && layer.getLatLng().lng === lng) {
        layer.openPopup();
      }
    });
  };

  // Couleur du point chaud selon son FRP
  function getFrpColor(frp) {
    if (frp >= 50.0) return '#dc2626'; // Critique
    if (frp >= 15.0) return '#ea580c'; // Élevé
    if (frp >= 5.0)  return '#f59e0b'; // Modéré
    return '#22c55e';                  // Faible
  }

  // ─── Contrôles & Filtres ───

  window.onZoneChange = function() {
    const select = document.getElementById('zoneSelect');
    const val = select.value;

    if (val === 'custom') {
      currentZoneId = 'custom';
      return;
    }

    const opt = select.options[select.selectedIndex];
    currentZoneId = val;
    currentLat = parseFloat(opt.getAttribute('data-lat'));
    currentLng = parseFloat(opt.getAttribute('data-lng'));
    currentZoneName = opt.getAttribute('data-name');

    updateCenterRadiusOverlay();
    map.setView([currentLat, currentLng], currentRadius === 'all' ? 7 : 9, { animate: true });
    fetchFires();
  };

  window.setRadius = function(r) {
    currentRadius = r;
    document.querySelectorAll('.radius-btn').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-radius') == r);
    });
    updateCenterRadiusOverlay();
    fetchFires();
  };

  window.setDays = function(d) {
    currentDays = d;
    document.querySelectorAll('.days-btn').forEach(btn => {
      btn.classList.toggle('active', parseInt(btn.getAttribute('data-days')) === d);
    });
    fetchFires();
  };

  window.onSourceChange = function() {
    currentSource = document.getElementById('sourceSelect').value;
    fetchFires();
  };

  window.refreshFiresData = function() {
    fetchFires();
  };

  window.locateUserPosition = function() {
    if (!navigator.geolocation) {
      showFireToast("La géolocalisation n'est pas supportée par votre navigateur.", "warning");
      return;
    }

    const loadingOverlay = document.getElementById('mapLoadingOverlay');
    if (loadingOverlay) loadingOverlay.classList.remove('d-none');

    navigator.geolocation.getCurrentPosition(
      pos => {
        currentLat = parseFloat(pos.coords.latitude.toFixed(4));
        currentLng = parseFloat(pos.coords.longitude.toFixed(4));
        currentZoneName = "Ma Position Actuelle (GPS)";
        currentZoneId = 'custom';

        const select = document.getElementById('zoneSelect');
        if (select) select.value = 'custom';

        updateCenterRadiusOverlay();
        map.setView([currentLat, currentLng], 10, { animate: true });
        fetchFires();
      },
      err => {
        if (loadingOverlay) loadingOverlay.classList.add('d-none');
        showFireToast("Impossible de récupérer votre position GPS. Veuillez autoriser la localisation.", "warning");
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  };

  window.toggleRadiusCircle = function() {
    if (!radiusCircle) return;
    if (map.hasLayer(radiusCircle)) {
      map.removeLayer(radiusCircle);
    } else {
      radiusCircle.addTo(map);
    }
  };

  window.recenterMap = function() {
    if (!map) return;
    map.setView([currentLat, currentLng], currentRadius === 'all' ? 7 : 9, { animate: true });
  };


  // ─── Export CSV ───

  window.exportFiresCsv = function() {
    const headers = [
      'ID',
      'Latitude',
      'Longitude',
      'Localite_Zone',
      'Gouvernorat',
      'Puissance_FRP_MW',
      'Temperature_Brillance_K',
      'Niveau_Confiance',
      'Satellite_Instrument',
      'Date_Acquisition',
      'Heure_UTC',
      'Periode_Jour_Nuit',
      'Distance_KM'
    ];

    let rows = [];

    if (cachedHotspots && cachedHotspots.length > 0) {
      rows = cachedHotspots.map((h, i) => [
        i + 1,
        h.latitude,
        h.longitude,
        `"${(h.locality || currentZoneName || '').replace(/"/g, '""')}"`,
        `"${(h.gouvernorat || '').replace(/"/g, '""')}"`,
        h.frp_mw,
        h.brightness_k,
        `"${(h.confidence || '').replace(/"/g, '""')}"`,
        `"${(h.satellite || '').replace(/"/g, '""')}"`,
        h.acq_date,
        h.acq_time,
        `"${h.daynight || ''}"`,
        h.distance_km !== null && h.distance_km !== undefined ? h.distance_km : ''
      ]);
    } else {
      // Si aucun foyer actif n'est détecté, générer une ligne d'information officielle
      rows = [
        [1, currentLat, currentLng, `"${(currentZoneName || '').replace(/"/g, '""')}"`, 'N/A', 0, 0, '"Aucun départ de feu actif détecté"', '"NASA FIRMS"', new Date().toISOString().slice(0, 10), new Date().toTimeString().slice(0, 5), 'N/A', 0]
      ];
    }

    // Utilisation de Blob avec UTF-8 BOM (\uFEFF) pour compatibilité totale avec Excel
    const csvContent = '\uFEFF' + [
      headers.join(';'),
      ...rows.map(r => r.join(';'))
    ].join('\r\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const cleanZoneName = (currentZoneName || 'Tunisie').replace(/[^a-zA-Z0-9_\-]/g, '_');
    const fileName = `HeatAlert_NASA_FIRMS_${cleanZoneName}_${new Date().toISOString().slice(0,10)}.csv`;

    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', fileName);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => URL.revokeObjectURL(url), 100);
  };

  // ─── Impression Pro (Rapport Officiel) ───

  window.printProReport = function() {
    // Remplissage du conteneur de rapport d'impression
    const now = new Date();
    document.getElementById('printReportTimestamp').textContent = `Édité le ${now.toLocaleDateString()} à ${now.toLocaleTimeString()}`;
    document.getElementById('printZoneName').textContent = currentZoneName;
    document.getElementById('printCoordinates').textContent = `${currentLat.toFixed(4)} N, ${currentLng.toFixed(4)} E`;
    document.getElementById('printRadius').textContent = currentRadius === 'all' ? 'Territoire National' : `${currentRadius} km`;
    document.getElementById('printTimeRange').textContent = `Dernières ${currentDays * 24} heures (${currentDays} j)`;

    const sourceSelect = document.getElementById('sourceSelect');
    document.getElementById('printSourceSensor').textContent = sourceSelect ? sourceSelect.options[sourceSelect.selectedIndex].text : currentSource;

    const riskStatus = document.getElementById('kpiRiskStatus')?.textContent || 'Surveillance normale';
    document.getElementById('printRiskStatus').textContent = riskStatus;

    document.getElementById('printTotalFires').textContent = cachedHotspots.length;
    document.getElementById('printMaxFrp').textContent = document.getElementById('kpiMaxFrp')?.textContent || '0 MW';
    document.getElementById('printClosestDist').textContent = document.getElementById('kpiClosestDist')?.textContent + ' km';

    // Remplir le tableau
    const tbody = document.getElementById('printHotspotsTableBody');
    if (tbody) {
      if (cachedHotspots.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="padding: 10px; text-align: center; color: #64748b;">Aucun foyer actif détecté par satellite dans ce périmètre.</td></tr>`;
      } else {
        tbody.innerHTML = cachedHotspots.map((h, i) => `
          <tr style="border-bottom: 1px solid #e2e8f0;">
            <td style="padding: 5px; border: 1px solid #e2e8f0; font-weight: bold;">${i + 1}</td>
            <td style="padding: 5px; border: 1px solid #e2e8f0;">${h.latitude.toFixed(4)}, ${h.longitude.toFixed(4)}</td>
            <td style="padding: 5px; border: 1px solid #e2e8f0;">${h.locality || '-'} ${h.gouvernorat ? '(' + h.gouvernorat + ')' : ''}</td>
            <td style="padding: 5px; border: 1px solid #e2e8f0; font-weight: bold; color: #dc2626;">${h.frp_mw} MW</td>
            <td style="padding: 5px; border: 1px solid #e2e8f0;">${h.brightness_k} K</td>
            <td style="padding: 5px; border: 1px solid #e2e8f0;">${h.distance_km !== null ? h.distance_km + ' km' : '-'}</td>
            <td style="padding: 5px; border: 1px solid #e2e8f0;">${h.acq_date} ${h.acq_time}</td>
            <td style="padding: 5px; border: 1px solid #e2e8f0;">${h.confidence}</td>
          </tr>
        `).join('');
      }
    }

    // Déclenchement de l'impression
    window.print();
  };

  // ═══════════════════════════════════════════════════════════
  // ─── NOTIFICATION TOAST PERSONNALISÉE (SANS ALERT NAVIGATEUR)
  // ═══════════════════════════════════════════════════════════

  let fireToastTimer = null;
  window.showFireToast = function(message, type = 'warning') {
    const toast = document.getElementById('fireToastNotification');
    const msgEl = document.getElementById('fireToastMessage');
    const iconEl = document.getElementById('fireToastIcon');
    if (!toast || !msgEl) return;

    msgEl.textContent = message;

    const config = {
      warning: { icon: 'bi-exclamation-triangle-fill', textClass: 'text-warning', border: '#f59e0b' },
      info:    { icon: 'bi-info-circle-fill',         textClass: 'text-primary', border: '#3b82f6' },
      danger:  { icon: 'bi-fire',                     textClass: 'text-danger',  border: '#dc2626' },
      success: { icon: 'bi-check-circle-fill',        textClass: 'text-success', border: '#10b981' }
    };
    const c = config[type] || config.warning;

    if (iconEl) {
      iconEl.className = `me-3 fs-3 ${c.textClass}`;
      iconEl.innerHTML = `<i class="bi ${c.icon}"></i>`;
    }
    toast.style.borderLeft = `5px solid ${c.border}`;
    toast.style.display = 'block';
    toast.classList.remove('toast-slide-animate');
    void toast.offsetWidth; // Force le recalcul du layout pour réactiver l'animation fluide
    toast.classList.add('show');
    toast.classList.add('toast-slide-animate');

    if (fireToastTimer) clearTimeout(fireToastTimer);
    fireToastTimer = setTimeout(() => {
      hideFireToast();
    }, 4500);
  };

  window.hideFireToast = function() {
    const toast = document.getElementById('fireToastNotification');
    if (!toast) return;
    toast.classList.remove('show');
    toast.classList.remove('toast-slide-animate');
    setTimeout(() => {
      toast.style.display = 'none';
    }, 250);
  };

  // ═══════════════════════════════════════════════════════════
  // ─── PRÉDICTION ML DE PROPAGATION DU FEU (LIGHTGBM 24H) ───
  // ═══════════════════════════════════════════════════════════

  window.runFireSpreadPrediction = function() {
    // Si aucun foyer actif n'est actuellement détecté, afficher le toast personnalisé
    if (!cachedHotspots || cachedHotspots.length === 0) {
      showFireToast("Aucun foyer actif détecté pour calculer la propagation.", "warning");
      return;
    }

    const btn = document.getElementById('btnPredictSpread');
    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Calcul LightGBM...';
    }

    const payload = {
      hotspots: cachedHotspots,
      lat: currentLat,
      lng: currentLng,
      radius: currentRadius,
      days: currentDays,
      source: currentSource
    };

    fetch("{{ route('front.fires.predict_spread') }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
      }

      if (!data.success) {
        showFireToast(data.message || "Aucun foyer actif détecté pour calculer la propagation.", "warning");
        return;
      }

      lastSpreadData = data;
      renderFireSpreadPredictions(data);
    })
    .catch(err => {
      console.error('Erreur Prédiction ML :', err);
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
      }
      showFireToast("Erreur lors de la communication avec le microservice ML.", "danger");
    });
  };

  function renderFireSpreadPredictions(data) {
    if (!spreadLayerGroup) return;
    spreadLayerGroup.clearLayers();

    const preds = data.predictions || [];
    const summary = data.summary || {};
    const threatenedZones = data.threatened_zones || [];

    // 1. Marqueurs circulaires sur la carte Leaflet
    const levelColors = {
      critique: { color: '#dc2626', fill: '#dc2626', radius: 14, opacity: 0.85, label: 'CRITIQUE' },
      eleve:    { color: '#ea580c', fill: '#ea580c', radius: 11, opacity: 0.75, label: 'ÉLEVÉ' },
      modere:   { color: '#f59e0b', fill: '#f59e0b', radius: 8,  opacity: 0.65, label: 'MODÉRÉ' },
      faible:   { color: '#10b981', fill: '#10b981', radius: 6,  opacity: 0.50, label: 'FAIBLE' }
    };

    preds.forEach(p => {
      const cfg = levelColors[p.niveau] || levelColors.faible;
      const probPct = (p.probabilite * 100).toFixed(1);

      const circle = L.circleMarker([p.lat, p.lon], {
        radius: cfg.radius,
        color: cfg.color,
        fillColor: cfg.fill,
        fillOpacity: cfg.opacity,
        weight: p.niveau === 'critique' ? 3 : 1.5,
        dashArray: p.niveau === 'critique' ? '4, 4' : null
      });

      const factorsHtml = (p.facteurs_principaux || []).map(f => `<li class="small text-muted">${f}</li>`).join('');

      const popupHtml = `
        <div style="min-width: 250px; font-family: sans-serif;">
          <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
            <span class="badge" style="background: ${cfg.color}; color: #fff; font-weight: bold; font-size: 11px;">
              RISQUE ${cfg.label}
            </span>
            <strong style="color: ${cfg.color}; font-size: 15px;">${probPct}%</strong>
          </div>
          <div class="small mb-2">
            <strong>Coordonnées :</strong> ${p.lat.toFixed(3)} N, ${p.lon.toFixed(3)} E<br>
            <strong>Distance au front :</strong> ${p.dist_closest_fire_km ? p.dist_closest_fire_km.toFixed(1) + ' km' : 'N/A'}<br>
            ${p.threatened_zone ? `<strong class="text-danger">Zone :</strong> ${p.threatened_zone}` : ''}
          </div>
          <div class="small fw-bold text-dark border-top pt-1 mt-1">Facteurs de propagation 24h :</div>
          <ul class="ps-3 mb-0" style="padding-left: 16px; margin-bottom: 0;">
            ${factorsHtml}
          </ul>
        </div>
      `;

      circle.bindPopup(popupHtml);
      circle.bindTooltip(`Propagation : ${probPct}% (${cfg.label})`, { direction: 'top', offset: [0, -5] });
      spreadLayerGroup.addLayer(circle);
    });

    // 2. Mise à jour des KPIs
    document.getElementById('mlKpiCritique').textContent = summary.critique || 0;
    document.getElementById('mlKpiEleve').textContent = summary.eleve || 0;
    const estimatedAreaKm2 = ((summary.critique || 0) + (summary.eleve || 0)) * 25;
    document.getElementById('mlKpiArea').textContent = `${estimatedAreaKm2} km²`;
    document.getElementById('mlKpiThreatenedCount').textContent = threatenedZones.length;
    document.getElementById('mlBadgeZonesCount').textContent = `${threatenedZones.length} zone(s)`;
    if (data.model_version) {
      document.getElementById('mlModelVersionBadge').textContent = data.model_version;
    }

    // 3. Mise à jour du tableau des zones menacées
    const tbody = document.getElementById('mlThreatenedZonesBody');
    if (tbody) {
      if (threatenedZones.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center py-4 text-success fw-semibold">
              <i class="bi bi-shield-check fs-4 d-block mb-1"></i>
              Aucune zone urbaine ou agglomération habitée directement menacée dans les 24 prochaines heures.
            </td>
          </tr>
        `;
      } else {
        tbody.innerHTML = threatenedZones.map(z => {
          const isCrit = z.max_risk_level === 'critique';
          const badgeClass = isCrit ? 'bg-danger text-white' : 'bg-warning text-dark';
          const recText = isCrit
            ? '<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Alerte Rouge : Évacuation préventive & Coupe-feu</span>'
            : '<span class="text-warning-emphasis fw-semibold"><i class="bi bi-cone-striped me-1"></i>Vigilance Renforcée : Patrouille forestière</span>';

          return `
            <tr>
              <td class="ps-3 fw-bold text-dark">${z.nom}</td>
              <td>${z.gouvernorat || z.ville || '-'}</td>
              <td><span class="badge bg-light text-dark border">${z.distance_to_risk_cell_km} km</span></td>
              <td class="fw-bold">${(z.max_probability * 100).toFixed(1)}%</td>
              <td><span class="badge ${badgeClass} text-uppercase px-2 py-1">${z.max_risk_level}</span></td>
              <td>${recText}</td>
            </tr>
          `;
        }).join('');
      }
    }

    // 4. Graphiques Chart.js
    renderSpreadCharts(summary);

    // 5. Afficher la section et scroller vers elle
    const section = document.getElementById('mlSpreadDashboardSection');
    if (section) {
      section.classList.remove('d-none');
      section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function renderSpreadCharts(summary) {
    if (typeof Chart === 'undefined') return;

    // Graphique 1 : Doughnut Répartition du risque
    const canvas1 = document.getElementById('chartSpreadDistribution');
    if (canvas1) {
      const ctx1 = canvas1.getContext('2d');
      if (chartDistribution) chartDistribution.destroy();
      chartDistribution = new Chart(ctx1, {
        type: 'doughnut',
        data: {
          labels: ['Critique (>=70%)', 'Élevé (45-70%)', 'Modéré (25-45%)', 'Faible (<25%)'],
          datasets: [{
            data: [
              summary.critique || 0,
              summary.eleve || 0,
              summary.modere || 0,
              summary.faible || 0
            ],
            backgroundColor: ['#dc2626', '#ea580c', '#f59e0b', '#10b981'],
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
          }
        }
      });
    }

    // Graphique 2 : Facteurs d'Influence ML
    const canvas2 = document.getElementById('chartSpreadFactors');
    if (canvas2) {
      const ctx2 = canvas2.getContext('2d');
      if (chartFactors) chartFactors.destroy();
      chartFactors = new Chart(ctx2, {
        type: 'bar',
        data: {
          labels: ['Alignement Vent', 'Intensité Foyer (FRP)', 'Indice Angström', 'Chaleur Caniculaire', 'Sécheresse Sols'],
          datasets: [{
            label: 'Poids Relatif d\'Impact ML (%)',
            data: [35, 25, 18, 14, 8],
            backgroundColor: ['#ea580c', '#dc2626', '#f59e0b', '#ef4444', '#8b5cf6'],
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          indexAxis: 'y',
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: { beginAtZero: true, max: 40, ticks: { callback: v => v + '%' } }
          }
        }
      });
    }
  }

  window.toggleSpreadLayer = function() {
    if (!map || !spreadLayerGroup) return;
    const btn = document.getElementById('btnToggleSpreadLayer');
    if (map.hasLayer(spreadLayerGroup)) {
      map.removeLayer(spreadLayerGroup);
      if (btn) {
        btn.classList.remove('active');
        btn.classList.remove('btn-danger');
        btn.classList.add('btn-outline-danger');
      }
    } else {
      map.addLayer(spreadLayerGroup);
      if (btn) {
        btn.classList.add('active');
        btn.classList.add('btn-danger');
        btn.classList.remove('btn-outline-danger');
      }
    }
  };

})();
</script>
@endpush
