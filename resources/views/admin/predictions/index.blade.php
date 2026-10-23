@extends('layouts.admin.admin')

@section('title', 'Prévisions de coupures')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
  @keyframes radarPulse {
    0% {
      transform: scale(0.9);
      opacity: 0.9;
    }
    70% {
      transform: scale(1.8);
      opacity: 0;
    }
    100% {
      transform: scale(2.2);
      opacity: 0;
    }
  }
  .radar-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    animation: radarPulse 1.8s infinite ease-out;
  }
  .leaflet-popup-content-wrapper {
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.14);
    padding: 0;
    overflow: hidden;
  }
  .leaflet-popup-content {
    margin: 0;
    line-height: 1.4;
  }
</style>
@endpush

@section('content')
<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-wand-magic-sparkles me-2 text-primary"></i>Prévisions de coupures</h1>
    <p class="subtitle">Dernier résultat disponible par zone, avec sa période de validité.</p>
  </div>
  <div class="page-header__actions d-flex align-items-center gap-2">
    <button type="button" id="btn-predict-all" class="btn btn-primary fw-semibold px-3 py-2 shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#predictAllConfirmModal">
      <i class="fa-solid fa-arrows-rotate"></i>
      <span>Prédire pour toutes les zones</span>
    </button>
  </div>
</div>

<div id="prediction-error" class="alert alert-danger d-none" role="alert"></div>

<div class="m-card mb-4 p-3" style="border-radius:14px;background:#ffffff;box-shadow:0 2px 10px rgba(0,0,0,0.03);">
  <form action="{{ route('admin.predictions.index') }}" method="GET" class="mb-3">
    @if(request('risk'))
      <input type="hidden" name="risk" value="{{ request('risk') }}">
    @endif
    @if(request('sort'))
      <input type="hidden" name="sort" value="{{ request('sort') }}">
    @endif
    <div class="row g-2 align-items-center">
      <div class="col-lg-8 col-md-9">
        <div class="input-group" style="border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
          <span class="input-group-text bg-white border-end-0 text-muted" style="border-color:#e2e8f0;padding-left:14px;">
            <i class="fa-solid fa-magnifying-glass"></i>
          </span>
          <input type="search" name="search" class="form-control border-start-0" style="border-color:#e2e8f0;" value="{{ request('search') }}" placeholder="Rechercher une zone, une ville ou un gouvernorat…">
          <button type="submit" class="btn px-3 fw-semibold text-white" style="background:#0ea5e9;border-color:#0ea5e9;">Rechercher</button>
        </div>
      </div>
      <div class="col-lg-4 col-md-3 d-flex justify-content-md-end gap-2">
        @if(request('search') || request('risk') || request('sort'))
          <a href="{{ route('admin.predictions.index') }}" class="btn btn-outline-secondary w-100 w-md-auto">
            <i class="fa-solid fa-rotate-left me-1"></i>Réinitialiser
          </a>
        @else
          <span class="text-muted small align-self-center">{{ $zones->total() }} zone(s) affichée(s)</span>
        @endif
      </div>
    </div>
  </form>

  {{-- Filtres rapides par niveau de risque --}}
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top" style="border-color:#f1f5f9 !important;">
    <div class="d-flex flex-wrap align-items-center gap-2">
      <span class="small fw-bold text-muted text-uppercase me-1" style="font-size:0.75rem;letter-spacing:0.05em;">
        <i class="fa-solid fa-filter me-1 text-primary"></i>Filtrer :
      </span>

      {{-- Toutes les zones --}}
      <a href="{{ route('admin.predictions.index', array_merge(request()->except(['risk', 'page']))) }}"
         class="btn btn-sm rounded-pill fw-semibold {{ !request('risk') ? 'btn-dark' : 'btn-outline-secondary' }}"
         style="font-size:0.82rem;padding:4px 12px;">
        Toutes les zones
        <span class="badge rounded-pill bg-light text-dark ms-1">{{ $counts['all'] ?? $zones->total() }}</span>
      </a>

      {{-- Risque Élevé --}}
      <a href="{{ route('admin.predictions.index', array_merge(request()->except(['page']), ['risk' => 'high'])) }}"
         class="btn btn-sm rounded-pill fw-semibold {{ request('risk') === 'high' ? 'btn-danger text-white' : 'btn-outline-danger' }}"
         style="font-size:0.82rem;padding:4px 12px;">
        <i class="fa-solid fa-triangle-exclamation me-1"></i>🔴 Risque Élevé
        <span class="badge rounded-pill {{ request('risk') === 'high' ? 'bg-white text-danger' : 'bg-danger text-white' }} ms-1">
          {{ $counts['high'] ?? 0 }}
        </span>
      </a>

      {{-- Risque Modéré --}}
      <a href="{{ route('admin.predictions.index', array_merge(request()->except(['page']), ['risk' => 'medium'])) }}"
         class="btn btn-sm rounded-pill fw-semibold {{ request('risk') === 'medium' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' }}"
         style="font-size:0.82rem;padding:4px 12px;">
        <i class="fa-solid fa-circle-exclamation me-1"></i>🟡 Risque Modéré
        <span class="badge rounded-pill {{ request('risk') === 'medium' ? 'bg-dark text-warning' : 'bg-warning text-dark' }} ms-1">
          {{ $counts['medium'] ?? 0 }}
        </span>
      </a>

      {{-- Risque Faible --}}
      <a href="{{ route('admin.predictions.index', array_merge(request()->except(['page']), ['risk' => 'low'])) }}"
         class="btn btn-sm rounded-pill fw-semibold {{ request('risk') === 'low' ? 'btn-success text-white' : 'btn-outline-success' }}"
         style="font-size:0.82rem;padding:4px 12px;">
        <i class="fa-solid fa-circle-check me-1"></i>🟢 Risque Faible
        <span class="badge rounded-pill {{ request('risk') === 'low' ? 'bg-white text-success' : 'bg-success text-white' }} ms-1">
          {{ $counts['low'] ?? 0 }}
        </span>
      </a>
    </div>

    {{-- Tri rapide par score de risque --}}
    <div class="d-flex align-items-center gap-1 ms-auto">
      <span class="small text-muted me-1">Trier :</span>
      @if(request('sort') === 'risk_desc')
        <a href="{{ route('admin.predictions.index', array_merge(request()->except(['sort', 'page']))) }}" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" style="font-size:0.8rem;">
          <i class="fa-solid fa-arrow-down-wide-short me-1"></i>Risque décroissant (Actif)
        </a>
      @else
        <a href="{{ route('admin.predictions.index', array_merge(request()->except(['page']), ['sort' => 'risk_desc'])) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3" style="font-size:0.8rem;">
          <i class="fa-solid fa-arrow-down-wide-short me-1"></i>Trier par risque le + élevé
        </a>
      @endif
    </div>
  </div>
</div>

{{-- Carte Interactive de Vigilance Électrique (Leaflet Heat & Risk Map) --}}
<div class="m-card mb-4 overflow-hidden" style="border-radius:16px;background:#ffffff;box-shadow:0 3px 12px rgba(0,0,0,0.04);">
  <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2" style="background:#f8fafc;">
    <div class="d-flex align-items-center gap-2">
      <div style="width:36px;height:36px;border-radius:10px;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
        <i class="fa-solid fa-map-location-dot"></i>
      </div>
      <div>
        <h2 class="h6 fw-bold mb-0 text-dark">Cartographie Nationale du Risque de Coupure</h2>
        <span class="text-muted small">Surveillance thermique et probabilités calculées par IA</span>
      </div>
    </div>
    <div class="d-flex align-items-center gap-3 small">
      <span class="d-flex align-items-center gap-1">
        <span class="d-inline-block rounded-circle" style="width:12px;height:12px;background:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,0.25);"></span>
        <strong class="text-danger">Risque Élevé (&ge; 70%)</strong>
      </span>
      <span class="d-flex align-items-center gap-1">
        <span class="d-inline-block rounded-circle" style="width:12px;height:12px;background:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,0.25);"></span>
        <strong class="text-warning">Risque Modéré</strong>
      </span>
      <span class="d-flex align-items-center gap-1">
        <span class="d-inline-block rounded-circle" style="width:12px;height:12px;background:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,0.25);"></span>
        <strong class="text-success">Risque Faible</strong>
      </span>
    </div>
  </div>
  <div id="predictionsRiskMap" style="height: 380px; width: 100%; background: #e2e8f0; position: relative;"></div>
</div>

<div class="row g-4">
  @forelse($zones as $zone)
    @php
      $prediction = $zone->latestPrediction;
      $isStale = $prediction && $prediction->created_at->lt(now()->subMinutes($staleAfterMinutes));
      $probability = $prediction?->probability;
      $inputSnapshot = $prediction?->input_snapshot ?? [];
      $riskClass = $prediction?->risk_level === 'high' ? 'danger' : ($prediction?->risk_level === 'medium' ? 'warning' : 'success');
      $displayVariables = [
        'temperature_max' => 'Température max',
        'temperature_ressentie' => 'Ressenti',
        'humidite' => 'Humidité',
        'vitesse_vent' => 'Vent',
        'nb_coupures_7j' => 'Coupures sur 7 jours',
        'nb_signalements_valides_7j' => 'Signalements validés sur 7 jours',
      ];
    @endphp
    <div class="col-xl-6">
      <article class="m-card h-100" style="overflow:hidden;">
        <div class="d-flex justify-content-between align-items-start gap-3 p-3 border-bottom" style="border-color:#eef2f7 !important;">
          <div>
            <div class="d-flex align-items-center gap-2">
              <span class="d-inline-flex align-items-center justify-content-center rounded-3" style="width:38px;height:38px;background:rgba(14,165,233,.1);color:#0284c7;"><i class="fa-solid fa-location-dot"></i></span>
              <div>
                <h2 class="h5 mb-0">{{ $zone->nom }}</h2>
                <div class="small text-muted">{{ $zone->ville }}{{ $zone->gouvernorat ? ' · ' . $zone->gouvernorat : '' }}</div>
              </div>
            </div>
          </div>
          @if($prediction && $isStale)
            <span class="badge bg-warning text-dark" title="Plus de 24 heures">
              <i class="fa-solid fa-clock me-1"></i>Ancienne
            </span>
          @elseif($prediction)
            <span class="badge bg-success-subtle text-success border border-success-subtle">Disponible</span>
          @else
            <span class="badge bg-light text-secondary border">Aucune donnée</span>
          @endif
        </div>

        <div class="p-3">
          @if($prediction)
            <div class="row g-3 align-items-center mb-3">
              <div class="col-sm-5">
                <div class="text-muted text-uppercase fw-bold" style="font-size:.7rem;letter-spacing:.08em;">Probabilité estimée</div>
                <div class="display-6 fw-bold text-primary">{{ number_format($probability * 100, 1, ',', ' ') }}<span class="fs-5">%</span></div>
                <small class="text-muted">Risque de coupure dans les 24h</small>
              </div>
              <div class="col-sm-7">
                <div class="p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                  <div class="small text-muted mb-1"><i class="fa-solid fa-calendar-day me-1"></i>Période de surveillance</div>
                  <strong class="d-block small">{{ $prediction->target_window_start?->format('d/m/Y H:i') }} → {{ $prediction->target_window_end?->format('d/m/Y H:i') }}</strong>

                  @if($prediction->estimated_outage_start && $prediction->estimated_outage_end)
                    <div class="mt-2 pt-2 border-top">
                      <div class="small fw-bold text-danger">
                        <i class="fa-solid fa-bolt me-1"></i>Coupure estimée :
                      </div>
                      <div class="fw-bold text-dark small">
                        {{ $prediction->estimated_outage_start->format('d/m/Y H:i') }} → {{ $prediction->estimated_outage_end->format('H:i') }}
                      </div>
                      @if($prediction->estimated_outage_cause)
                        <div class="text-muted" style="font-size:.75rem;">
                          <i class="fa-solid fa-circle-info me-1"></i>{{ $prediction->estimated_outage_cause }}
                        </div>
                      @endif
                    </div>
                  @endif

                  <div class="small text-muted mt-2" style="font-size:.75rem;">Mise à jour {{ $prediction->created_at?->diffForHumans() }}</div>
                </div>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-3">
              @if($prediction->risk_level)
                @php
                  $riskLabel = match($prediction->risk_level) {
                    'high'   => ['class'=>'danger',  'icon'=>'fa-triangle-exclamation', 'text'=>'Risque élevé'],
                    'medium' => ['class'=>'warning',  'icon'=>'fa-circle-exclamation',  'text'=>'Risque modéré'],
                    default  => ['class'=>'success',  'icon'=>'fa-circle-check',         'text'=>'Risque faible'],
                  };
                @endphp
                <span class="badge bg-{{ $riskLabel['class'] }}"><i class="fa-solid {{ $riskLabel['icon'] }} me-1"></i>{{ $riskLabel['text'] }}</span>
              @endif
              @if($isStale)
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Actualiser recommandé</span>
              @endif
            </div>

            {{-- Variables météo & historique --}}
            @php
              $unit = [
                'temperature_max'            => ['label'=>'Temp. max',             'unit'=>'°C',  'icon'=>'fa-temperature-high'],
                'temperature_ressentie'      => ['label'=>'Ressenti',              'unit'=>'°C',  'icon'=>'fa-thermometer-half'],
                'humidite'                   => ['label'=>'Humidité',              'unit'=>'%',   'icon'=>'fa-droplet'],
                'vitesse_vent'               => ['label'=>'Vent',                  'unit'=>'km/h','icon'=>'fa-wind'],
                'nb_coupures_24h'            => ['label'=>'Coupures (24 h)',       'unit'=>'',    'icon'=>'fa-bolt'],
                'nb_coupures_7j'             => ['label'=>'Coupures (7 j)',        'unit'=>'',    'icon'=>'fa-bolt'],
                'nb_coupures_30j'            => ['label'=>'Coupures (30 j)',       'unit'=>'',    'icon'=>'fa-bolt'],
                'nb_signalements_valides_7j' => ['label'=>'Signalements (7 j)',    'unit'=>'',    'icon'=>'fa-flag'],
                'jours_depuis_coupure'       => ['label'=>'Dernière coupure',      'unit'=>'j',   'icon'=>'fa-clock-rotate-left'],
              ];
              $hasWeather = !is_null($inputSnapshot['temperature_max'] ?? null)
                         || !is_null($inputSnapshot['humidite'] ?? null);
            @endphp

            <div class="border rounded-3 p-3 mb-3" style="background:#f8fafc;">
              <div class="small fw-semibold text-secondary mb-2">
                <i class="fa-solid fa-chart-bar me-1"></i>Données utilisées pour la prédiction
              </div>

              @if(!$hasWeather)
                <div class="alert alert-info py-2 px-3 small mb-2">
                  <i class="fa-solid fa-circle-info me-1"></i>
                  Aucune alerte météo active pour cette zone — les données climatiques ne sont pas disponibles.
                </div>
              @endif

              <div class="row g-2">
                @foreach($unit as $key => $meta)
                  @if(array_key_exists($key, $inputSnapshot))
                    @php $val = $inputSnapshot[$key]; @endphp
                    <div class="col-6 col-md-4">
                      <div class="p-2 rounded-2 h-100" style="background:white;border:1px solid #e2e8f0;">
                        <div class="small text-muted mb-1">
                          <i class="fa-solid {{ $meta['icon'] }} me-1 text-primary" style="font-size:.7rem;"></i>{{ $meta['label'] }}
                        </div>
                        @if($val === null)
                          <span class="small text-muted fst-italic">—</span>
                        @else
                          <strong class="small">{{ is_float($val) ? number_format($val, 1, ',', ' ') : $val }}{{ $meta['unit'] ? ' '.$meta['unit'] : '' }}</strong>
                        @endif
                      </div>
                    </div>
                  @endif
                @endforeach
              </div>
            </div>
          @else
            <div class="text-center py-4">
              <span class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:54px;height:54px;background:#f1f5f9;color:#64748b;font-size:1.25rem;"><i class="fa-solid fa-chart-line"></i></span>
              <h3 class="h6">Aucune prédiction disponible</h3>
              <p class="text-muted small mb-0">Lance une première estimation pour cette zone.</p>
            </div>
          @endif

          <div class="d-flex justify-content-end align-items-center gap-1 gap-sm-2 pt-2 border-top overflow-x-auto" style="border-color:#eef2f7 !important;">
            @if($prediction)
              <button type="button"
                      class="btn btn-sm btn-outline-warning text-dark fw-semibold js-play-admin-voice d-inline-flex align-items-center gap-1 text-nowrap"
                      style="height:32px;padding:0 8px;font-size:11.5px;background:#fef3c7;border-color:#fde68a;"
                      data-zone-id="{{ $zone->id }}"
                      data-zone-name="{{ $zone->nom }}"
                      title="Écouter le bulletin vocal adapté au niveau de risque (بالدارجة)">
                <i class="fa-solid fa-volume-high text-warning-emphasis"></i><span class="d-none d-sm-inline">Vocal</span> 🇹🇳
              </button>
            @endif
            @if($prediction && ($prediction->probability >= 0.70 || $prediction->risk_level === 'high'))
              <button type="button"
                      class="btn btn-sm btn-outline-danger fw-semibold js-notify-residents d-inline-flex align-items-center gap-1 text-nowrap"
                      style="height:32px;padding:0 8px;font-size:11.5px;"
                      data-url="{{ route('admin.predictions.notify_residents', $zone) }}"
                      data-zone-name="{{ $zone->nom }}"
                      title="Envoyer une notification push & email aux résidents">
                <i class="fa-solid fa-bullhorn"></i>Alerter
              </button>
            @endif
            @if($prediction && ($prediction->probability >= 0.70 || $prediction->risk_level === 'high') && $prediction->estimated_outage_start)
              <a href="{{ route('admin.coupures.create', [
                  'zone_id'    => $zone->id,
                  'date_debut' => $prediction->estimated_outage_start->format('Y-m-d\TH:i'),
                  'date_fin'   => $prediction->estimated_outage_end ? $prediction->estimated_outage_end->format('Y-m-d\TH:i') : '',
                  'type'       => str_contains(strtolower($prediction->estimated_outage_cause ?? ''), 'surcharge') ? 'surcharge' : 'delestage',
                  'cause'      => 'Surcharge thermique suite prédiction météo (' . number_format($prediction->probability * 100, 1) . '% de risque)',
              ]) }}" class="m-btn btn-danger text-white fw-semibold d-inline-flex align-items-center gap-1 text-nowrap" style="height:32px;padding:0 8px;font-size:11.5px;background:#dc2626;border-color:#dc2626;" title="Planifier cette coupure préventive (Délestage STEG)">
                <i class="fa-solid fa-bolt"></i>Planifier
              </a>
            @endif
            <a href="{{ route('admin.zones.show', $zone) }}" class="m-btn m-btn--ghost d-inline-flex align-items-center gap-1 text-nowrap" style="height:32px;padding:0 8px;font-size:11.5px;">
              <i class="fa-solid fa-eye"></i>Voir zone
            </a>
            @if($zone->latitude !== null && $zone->longitude !== null)
              <button type="button" class="m-btn m-btn--primary js-predict-zone d-inline-flex align-items-center gap-1 text-nowrap" style="height:32px;padding:0 10px;font-size:11.5px;" data-url="{{ route('admin.zones.prediction', $zone) }}">
                <i class="fa-solid fa-rotate"></i>{{ $prediction ? 'Actualiser' : 'Prédire' }}
              </button>
            @else
              <span class="m-btn m-btn--ghost disabled text-nowrap" style="height:32px;padding:0 8px;font-size:11.5px;opacity:.6;" title="Coordonnées manquantes">
                <i class="fa-solid fa-location-crosshairs me-1"></i>Requis
              </span>
            @endif
          </div>
        </div>
      </article>
    </div>
  @empty
    <div class="col-12">
      <div class="m-card text-center py-5">
        <i class="fa-solid fa-map-location-dot fa-2x text-secondary mb-3"></i>
        <h2 class="h5">Aucune zone trouvée</h2>
        <p class="text-muted mb-0">Modifie la recherche ou vérifie les zones enregistrées.</p>
      </div>
    </div>
  @endforelse
</div>

@if($zones->hasPages())
  <div class="d-flex justify-content-center mt-4">{{ $zones->links() }}</div>
@endif
@endsection

{{-- Modal de Confirmation Global placé hors du conteneur pour éviter les conflits de z-index/backdrop --}}
<div class="modal fade" id="predictAllConfirmModal" tabindex="-1" aria-labelledby="predictAllConfirmModalLabel" aria-hidden="true" style="z-index: 1065;">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
      <div class="modal-header border-0 pb-0 pt-4 px-4 position-relative">
        <div class="d-flex align-items-center gap-3">
          <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(14, 165, 233, 0.12); color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-dark mb-0" id="predictAllConfirmModalLabel" style="font-size: 1.15rem;">Prédiction Globale</h5>
            <span class="text-primary small fw-semibold"><i class="fa-solid fa-cloud-bolt me-1"></i>Actualisation automatique</span>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer" style="margin-top: -12px; margin-right: -4px;"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <p class="mb-3 text-secondary" style="font-size: 0.95rem;">
          Voulez-vous lancer le calcul de prédiction de coupures pour <strong>toutes les zones actives</strong> du réseau ?
        </p>
        <div class="p-3 rounded-3 bg-light border border-light-subtle d-flex align-items-center gap-2">
          <i class="fa-solid fa-circle-nodes text-primary flex-shrink-0"></i>
          <span class="fw-bold text-dark text-truncate" style="font-size: 0.92rem;">
            Toutes les zones de surveillance ({{ $zones->total() }} zones)
          </span>
        </div>
        <small class="text-muted mt-2 d-block" style="font-size: 0.8rem;">
          <i class="fa-solid fa-info-circle me-1"></i>Cette opération interroge le modèle ML et la météo en temps réel pour chaque zone.
        </small>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 pt-1 d-flex gap-2">
        <button type="button" class="m-btn m-btn--ghost flex-grow-1 text-center py-2" data-bs-dismiss="modal">
          <i class="fa-solid fa-xmark me-2"></i>Annuler
        </button>
        <button type="button" id="btn-confirm-predict-all" class="btn btn-primary flex-grow-1 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 shadow-sm" style="border-radius: 8px;" data-url="{{ route('admin.predictions.predict_all') }}">
          <i class="fa-solid fa-wand-magic-sparkles"></i> Lancer les prédictions
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Modal de Confirmation Alerte Citoyens --}}
<div class="modal fade" id="notifyResidentsConfirmModal" tabindex="-1" aria-labelledby="notifyResidentsConfirmModalLabel" aria-hidden="true" style="z-index: 1065;">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
      <div class="modal-header border-0 pb-0 pt-4 px-4 position-relative">
        <div class="d-flex align-items-center gap-3">
          <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(220, 38, 38, 0.12); color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
            <i class="fa-solid fa-bullhorn"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-dark mb-0" id="notifyResidentsConfirmModalLabel" style="font-size: 1.15rem;">Alerte Résidents</h5>
            <span class="text-danger small fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Diffusion préventive</span>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer" style="margin-top: -12px; margin-right: -4px;"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <p class="mb-3 text-secondary" style="font-size: 0.95rem;">
          Envoyer une alerte préventive (notification in-app et e-mail) à tous les citoyens résidant dans cette zone ?
        </p>
        <div class="p-3 rounded-3 bg-light border border-light-subtle d-flex align-items-center gap-2">
          <i class="fa-solid fa-location-dot text-danger flex-shrink-0"></i>
          <span class="fw-bold text-dark text-truncate" id="notify-modal-zone-name" style="font-size: 0.92rem;">
            Zone ciblée
          </span>
        </div>
        <small class="text-muted mt-2 d-block" style="font-size: 0.8rem;">
          <i class="fa-solid fa-info-circle me-1"></i>Les citoyens recevront les consignes de précaution (recharge des téléphones, conservation du froid).
        </small>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 pt-1 d-flex gap-2">
        <button type="button" class="m-btn m-btn--ghost flex-grow-1 text-center py-2" data-bs-dismiss="modal">
          <i class="fa-solid fa-xmark me-2"></i>Annuler
        </button>
        <button type="button" id="btn-confirm-notify-residents" class="btn btn-danger flex-grow-1 text-center py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" style="border-radius: 8px;">
          <i class="fa-solid fa-paper-plane"></i>
          <span>Envoyer l'alerte</span>
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Modal Lecteur Bulletin Vocal Admin (Derja / FR) --}}
<div class="modal fade" id="adminVoiceModal" tabindex="-1" aria-labelledby="adminVoiceModalLabel" aria-hidden="true" style="z-index: 1065;">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
      <div class="modal-header border-0 pb-0 pt-4 px-4 position-relative">
        <div class="d-flex align-items-center gap-3">
          <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(245, 158, 11, 0.15); color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
            <i class="fa-solid fa-volume-high"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-dark mb-0" id="adminVoiceModalLabel" style="font-size: 1.15rem;">Bulletin Vocal IA (Derja / FR)</h5>
            <span class="text-warning-emphasis small fw-semibold"><i class="fa-solid fa-microphone me-1"></i>Alerte Audio Synthétisée</span>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer" style="margin-top: -12px; margin-right: -4px;"></button>
      </div>

      <div class="modal-body px-4 py-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span class="fw-bold text-dark" id="adminVoiceZoneName">Zone</span>
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-warning text-dark active js-admin-voice-lang" data-lang="ar-TN">🇹🇳 تونسي</button>
            <button type="button" class="btn btn-outline-secondary js-admin-voice-lang" data-lang="fr">🇫🇷 FR</button>
          </div>
        </div>

        <div class="p-3 rounded-3 bg-light border mb-3">
          <label class="small text-muted fw-semibold mb-1 d-block"><i class="fa-solid fa-file-lines me-1"></i>Script prononcé par l'IA :</label>
          <div id="adminVoiceScriptText" class="small text-dark" style="max-height: 120px; overflow-y: auto; font-style: italic; line-height: 1.5;">
            Chargement du script...
          </div>
        </div>

        <div class="p-3 rounded-3 d-flex align-items-center justify-content-between gap-3" style="background: #fffbeb; border: 1px solid #fef3c7;">
          <div class="d-flex align-items-center gap-2">
            <button type="button" id="btnAdminPlayVoice" class="btn btn-warning rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" style="width:42px;height:42px;flex-shrink:0;">
              <i class="fa-solid fa-play" id="iconAdminPlayVoice"></i>
            </button>
            <span class="small fw-semibold text-dark" id="adminVoiceStatus">Prêt pour l'écoute</span>
          </div>
          <audio id="adminAudioVoice" preload="none" class="d-none"></audio>
        </div>
      </div>

      <div class="modal-footer border-0 px-4 pb-4 pt-1">
        <button type="button" class="m-btn m-btn--ghost w-100 text-center py-2" data-bs-dismiss="modal">
          <i class="fa-solid fa-check me-1"></i>Fermer
        </button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const errorBox = document.getElementById('prediction-error');

  document.querySelectorAll('.js-predict-zone').forEach(function (button) {
    button.addEventListener('click', async function () {
      const original = button.innerHTML;
      button.disabled = true;
      button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Calcul…';
      errorBox.classList.add('d-none');

      try {
        const response = await fetch(button.dataset.url, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify({}),
        });
        const body = await response.json();
        if (!response.ok || !body.success) {
          throw new Error(body.message || 'Le service de prédiction est indisponible.');
        }
        window.location.reload();
      } catch (error) {
        errorBox.textContent = error.message;
        errorBox.classList.remove('d-none');
        button.disabled = false;
        button.innerHTML = original;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  });

  // Prédiction globale pour toutes les zones via la Modal Bootstrap
  const confirmPredictAllBtn = document.getElementById('btn-confirm-predict-all');
  const modalEl = document.getElementById('predictAllConfirmModal');

  if (confirmPredictAllBtn) {
    confirmPredictAllBtn.addEventListener('click', async function () {
      const originalHtml = confirmPredictAllBtn.innerHTML;
      confirmPredictAllBtn.disabled = true;
      confirmPredictAllBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Calcul en cours…';
      errorBox.classList.add('d-none');

      try {
        const response = await fetch(confirmPredictAllBtn.dataset.url, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify({}),
        });
        const body = await response.json();
        if (!response.ok || !body.success) {
          throw new Error(body.message || 'Erreur lors du calcul global.');
        }

        // Fermer la modal avant de recharger
        if (modalEl && typeof bootstrap !== 'undefined') {
          const bsModal = bootstrap.Modal.getInstance(modalEl);
          if (bsModal) bsModal.hide();
        }

        window.location.reload();
      } catch (error) {
        errorBox.textContent = error.message;
        errorBox.classList.remove('d-none');
        confirmPredictAllBtn.disabled = false;
        confirmPredictAllBtn.innerHTML = originalHtml;

        // Fermer la modal pour laisser voir l'erreur
        if (modalEl && typeof bootstrap !== 'undefined') {
          const bsModal = bootstrap.Modal.getInstance(modalEl);
          if (bsModal) bsModal.hide();
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  }

  // Alerte Citoyens par Zone
  const notifyModalEl = document.getElementById('notifyResidentsConfirmModal');
  const notifyZoneNameEl = document.getElementById('notify-modal-zone-name');
  const btnConfirmNotify = document.getElementById('btn-confirm-notify-residents');
  let currentNotifyUrl = '';

  document.querySelectorAll('.js-notify-residents').forEach(function(btn) {
    btn.addEventListener('click', function() {
      currentNotifyUrl = btn.dataset.url;
      if (notifyZoneNameEl) {
        notifyZoneNameEl.textContent = btn.dataset.zoneName || 'Zone sélectionnée';
      }
      if (notifyModalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(notifyModalEl);
        modal.show();
      }
    });
  });

  if (btnConfirmNotify) {
    btnConfirmNotify.addEventListener('click', async function() {
      if (!currentNotifyUrl) return;
      const originalHtml = btnConfirmNotify.innerHTML;
      btnConfirmNotify.disabled = true;
      btnConfirmNotify.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Envoi en cours…';
      errorBox.classList.add('d-none');

      try {
        const response = await fetch(currentNotifyUrl, {
          method: 'POST',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify({}),
        });
        const body = await response.json();
        if (!response.ok || !body.success) {
          throw new Error(body.message || 'Erreur lors de l’envoi des notifications.');
        }

        if (notifyModalEl && typeof bootstrap !== 'undefined') {
          const bsModal = bootstrap.Modal.getInstance(notifyModalEl);
          if (bsModal) bsModal.hide();
        }

        window.location.reload();
      } catch (error) {
        errorBox.textContent = error.message;
        errorBox.classList.remove('d-none');
        btnConfirmNotify.disabled = false;
        btnConfirmNotify.innerHTML = originalHtml;

        if (notifyModalEl && typeof bootstrap !== 'undefined') {
          const bsModal = bootstrap.Modal.getInstance(notifyModalEl);
          if (bsModal) bsModal.hide();
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    });
  }

  // Initialisation Carte Leaflet du Risque Coupure
  const mapElement = document.getElementById('predictionsRiskMap');
  if (mapElement && typeof L !== 'undefined') {
    const mapZones = @json($mapZones ?? []);
    
    // Centrage sur la Tunisie
    const map = L.map('predictionsRiskMap', {
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
      let color = '#64748b'; // Inconnu
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

      // Marqueur interactif Leaflet
      const markerHtml = `
        <div style="position:relative;width:28px;height:28px;display:flex;align-items:center;justify-content:center;">
          ${isCritical ? `<div class="radar-ring" style="background:${color};opacity:0.4;"></div>` : ''}
          <div style="width:22px;height:22px;border-radius:50%;background:${fillColor};border:2.5px solid #ffffff;box-shadow:0 3px 8px rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;color:#ffffff;font-size:10px;font-weight:bold;">
            <i class="fa-solid fa-bolt" style="font-size:9px;"></i>
          </div>
        </div>
      `;

      const customIcon = L.divIcon({
        html: markerHtml,
        className: 'custom-prediction-marker',
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -14],
      });

      const marker = L.marker([zone.lat, zone.lng], { icon: customIcon }).addTo(map);

      // Cercle de zone thermique coloré
      L.circle([zone.lat, zone.lng], {
        radius: isCritical ? 25000 : 15000,
        color: color,
        weight: isCritical ? 2 : 1.5,
        fillColor: fillColor,
        fillOpacity: isCritical ? 0.25 : 0.12,
      }).addTo(map);

      // Contenu du Popup
      const popupHtml = `
        <div style="min-width:210px;font-family:Inter,sans-serif;">
          <div style="padding:10px 14px;background:${isCritical ? '#fee2e2' : '#f8fafc'};border-bottom:1px solid #e2e8f0;">
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;color:${color};display:inline-block;margin-bottom:2px;">
              <i class="fa-solid fa-bolt me-1"></i>${riskLabel} ${prob !== null ? '(' + prob + '%)' : ''}
            </span>
            <h4 style="margin:0;font-size:14px;font-weight:800;color:#0f172a;">${zone.nom}</h4>
            <span style="font-size:11px;color:#64748b;">${zone.ville} (${zone.gouvernorat})</span>
          </div>
          <div style="padding:10px 14px;font-size:12px;color:#334155;">
            ${zone.outage_start ? `
              <div style="margin-bottom:6px;">
                <span style="color:#64748b;">Créneau estimé :</span><br>
                <strong style="color:${color};">${zone.outage_start} → ${zone.outage_end}</strong>
              </div>
            ` : ''}
            ${zone.cause ? `<div style="font-size:11px;color:#64748b;font-style:italic;margin-bottom:8px;">${zone.cause}</div>` : ''}
            <a href="${zone.url}" style="display:inline-block;font-size:11px;font-weight:600;color:#0284c7;text-decoration:none;">
              Consulter la zone →
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

  // 🎙️ GESTION DU BULLETIN VOCAL ADMIN (DERJA & FR)
  const adminVoiceModalEl   = document.getElementById('adminVoiceModal');
  const adminVoiceModal     = adminVoiceModalEl ? new bootstrap.Modal(adminVoiceModalEl) : null;
  const adminVoiceZoneName  = document.getElementById('adminVoiceZoneName');
  const adminVoiceScriptText= document.getElementById('adminVoiceScriptText');
  const adminVoiceStatus    = document.getElementById('adminVoiceStatus');
  const btnAdminPlayVoice   = document.getElementById('btnAdminPlayVoice');
  const iconAdminPlayVoice  = document.getElementById('iconAdminPlayVoice');
  const adminAudioVoice     = document.getElementById('adminAudioVoice');
  const adminLangButtons    = document.querySelectorAll('.js-admin-voice-lang');

  let currentAdminZoneId = null;
  let currentAdminLang   = 'ar-TN';

  function loadAdminScriptAndAudio(autoPlay = false) {
    if (!currentAdminZoneId) return;

    adminVoiceScriptText.textContent = 'Chargement du script IA en cours...';
    adminVoiceStatus.textContent = 'Chargement audio...';
    btnAdminPlayVoice.disabled = true;

    fetch(`/voice/script/${currentAdminZoneId}?lang=${currentAdminLang}`)
      .then(r => r.json())
      .then(data => {
        btnAdminPlayVoice.disabled = false;
        if (data.success) {
          adminVoiceScriptText.textContent = data.script;
          adminAudioVoice.src = `${data.audio_url}&t=${Date.now()}`;
          adminVoiceStatus.textContent = 'Prêt pour l\'écoute';
          if (autoPlay) {
            playAdminAudio();
          }
        } else {
          adminVoiceScriptText.textContent = 'Erreur: ' + (data.message || 'Impossible de charger le script.');
        }
      })
      .catch(err => {
        btnAdminPlayVoice.disabled = false;
        adminVoiceScriptText.textContent = 'Erreur de connexion au service vocal.';
      });
  }

  function playAdminAudio() {
    if (!adminAudioVoice.src) {
      loadAdminScriptAndAudio(true);
      return;
    }
    adminAudioVoice.play().then(() => {
      iconAdminPlayVoice.className = 'fa-solid fa-pause';
      adminVoiceStatus.textContent = currentAdminLang === 'ar-TN' ? 'قاعد يقرا بالدارجة...' : 'Lecture en cours...';
    }).catch(err => {
      console.warn('Audio play error:', err);
      iconAdminPlayVoice.className = 'fa-solid fa-play';
      adminVoiceStatus.textContent = 'Cliquez pour écouter.';
    });
  }

  function pauseAdminAudio() {
    adminAudioVoice.pause();
    iconAdminPlayVoice.className = 'fa-solid fa-play';
    adminVoiceStatus.textContent = 'En pause';
  }

  document.querySelectorAll('.js-play-admin-voice').forEach(btn => {
    btn.addEventListener('click', function() {
      currentAdminZoneId = this.getAttribute('data-zone-id');
      const zoneName = this.getAttribute('data-zone-name');
      adminVoiceZoneName.textContent = `Alerte Vocale — ${zoneName}`;
      adminAudioVoice.pause();
      iconAdminPlayVoice.className = 'fa-solid fa-play';

      if (adminVoiceModal) {
        adminVoiceModal.show();
      }
      loadAdminScriptAndAudio(false);
    });
  });

  if (btnAdminPlayVoice && adminAudioVoice) {
    btnAdminPlayVoice.addEventListener('click', function() {
      if (adminAudioVoice.paused || adminAudioVoice.ended) {
        playAdminAudio();
      } else {
        pauseAdminAudio();
      }
    });

    adminAudioVoice.addEventListener('ended', function() {
      iconAdminPlayVoice.className = 'fa-solid fa-rotate-right';
      adminVoiceStatus.textContent = 'Lecture terminée. Réécouter ?';
    });

    adminLangButtons.forEach(btn => {
      btn.addEventListener('click', function() {
        adminLangButtons.forEach(b => {
          b.classList.remove('active', 'btn-outline-warning', 'btn-outline-secondary');
          b.classList.add('btn-outline-secondary');
        });
        this.classList.add('active', 'btn-outline-warning');
        this.classList.remove('btn-outline-secondary');
        currentAdminLang = this.getAttribute('data-lang');

        adminAudioVoice.pause();
        iconAdminPlayVoice.className = 'fa-solid fa-play';
        loadAdminScriptAndAudio(false);
      });
    });

    if (adminVoiceModalEl) {
      adminVoiceModalEl.addEventListener('hidden.bs.modal', function() {
        adminAudioVoice.pause();
        iconAdminPlayVoice.className = 'fa-solid fa-play';
      });
    }
  }
});

</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endpush
