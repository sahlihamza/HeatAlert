@extends('layouts.admin.admin')

@section('title', 'Synchronisation STEG')

@section('content')
<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-plug-circle-bolt me-2 text-warning"></i>Intégration STEG Officielle</h1>
    <p class="subtitle">Synchronisez les coupures programmées publiées sur <strong>steg.com.tn</strong> avec HeatAlert.</p>
  </div>
  <div class="page-header__actions">
    <form action="{{ route('admin.steg.sync') }}" method="POST" id="syncForm">
      @csrf
      <button type="submit" class="m-btn m-btn--primary" id="syncBtn">
        <i class="fa-solid fa-rotate me-1"></i> Synchroniser maintenant
      </button>
    </form>
  </div>
</div>

{{-- ── STATUT DE LA DERNIÈRE SYNC ── --}}
@if(session('steg_result'))
  @php $res = session('steg_result'); @endphp
  <div class="alert alert-{{ $res['created'] > 0 ? 'success' : 'info' }} d-flex align-items-start gap-3 mb-4" style="border-radius:12px;">
    <i class="fa-solid fa-circle-check fa-lg mt-1"></i>
    <div>
      <strong>Synchronisation terminée</strong>
      <ul class="mb-0 mt-1">
        <li>✅ <strong>{{ $res['created'] }}</strong> coupure(s) importée(s)</li>
        <li>⏭️ <strong>{{ $res['skipped'] }}</strong> déjà existante(s) ignorée(s)</li>
        @if(!empty($res['matched']))
          <li>🗺️ Zones : <strong>{{ implode(', ', $res['matched']) }}</strong></li>
        @endif
        @if(!empty($res['errors']))
          <li class="text-danger">❌ {{ count($res['errors']) }} erreur(s)</li>
        @endif
      </ul>
    </div>
  </div>
@endif

{{-- ── STATS RAPIDES ── --}}
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="m-card p-3 text-center h-100" style="border-radius:12px;border-left:4px solid #f59e0b;">
      <div class="h2 fw-bold text-warning">{{ $stegCount }}</div>
      <div class="text-muted small">Coupures STEG importées</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="m-card p-3 text-center h-100" style="border-radius:12px;border-left:4px solid #22c55e;">
      <div class="h2 fw-bold text-success">{{ $totalItems }}</div>
      <div class="text-muted small">Items détectés (cache)</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="m-card p-3 text-center h-100" style="border-radius:12px;border-left:4px solid #3b82f6;">
      <div class="h2 fw-bold text-primary">{{ $source ?? '—' }}</div>
      <div class="text-muted small">Source active</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="m-card p-3 text-center h-100" style="border-radius:12px;border-left:4px solid #8b5cf6;">
      <div class="h6 fw-bold text-purple mt-2">{{ $fetchedAt ?? '—' }}</div>
      <div class="text-muted small">Dernière vérification</div>
    </div>
  </div>
</div>

{{-- ── TABLEAU DES COUPURES STEG DÉTECTÉES ── --}}
<div class="m-card mb-4" style="border-radius:14px;">
  <div class="m-card__header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fa-solid fa-list-check me-2 text-warning"></i>Coupures détectées depuis STEG</h5>
    <span class="badge bg-warning text-dark">{{ $totalItems }} items</span>
  </div>
  <div class="m-card__body p-0">
    @if($items->isEmpty())
      <div class="text-center py-5 text-muted">
        <i class="fa-solid fa-satellite-dish fa-3x mb-3 opacity-30"></i>
        <p>Aucune coupure détectée depuis STEG pour le moment.</p>
        <p class="small">Le site STEG peut être temporairement inaccessible.<br>Des données de démonstration seront utilisées automatiquement.</p>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th><i class="fa-solid fa-heading me-1"></i>Titre</th>
              <th><i class="fa-solid fa-tag me-1"></i>Type</th>
              <th><i class="fa-solid fa-map-marker-alt me-1"></i>Zones détectées</th>
              <th><i class="fa-solid fa-clock me-1"></i>Créneau</th>
              <th><i class="fa-solid fa-gauge me-1"></i>Confiance</th>
              <th><i class="fa-solid fa-database me-1"></i>Importée</th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $item)
              <tr>
                <td>
                  <div class="fw-semibold" style="max-width:280px;">
                    {{ Str::limit($item['titre'] ?? '—', 55) }}
                  </div>
                  @if(!empty($item['url']))
                    <a href="{{ $item['url'] }}" target="_blank" class="text-muted small text-decoration-none">
                      <i class="fa-solid fa-external-link fa-xs me-1"></i>steg.com.tn
                    </a>
                  @endif
                </td>
                <td>
                  @php
                    $typeColors = ['delestage' => 'warning', 'surcharge' => 'danger', 'panne' => 'secondary'];
                    $typeColor = $typeColors[$item['type'] ?? 'panne'] ?? 'secondary';
                  @endphp
                  <span class="badge bg-{{ $typeColor }}">{{ ucfirst($item['type'] ?? 'panne') }}</span>
                </td>
                <td>
                  @if(!empty($item['zones_detectees']))
                    @foreach($item['zones_detectees'] as $z)
                      <span class="badge bg-light text-dark border me-1 mb-1">🗺️ {{ ucfirst($z) }}</span>
                    @endforeach
                  @else
                    <span class="text-muted small">Non détecté</span>
                  @endif
                </td>
                <td class="small">
                  @if(!empty($item['date_debut']))
                    <div><i class="fa-solid fa-play text-success me-1"></i>{{ $item['date_debut']->format('d/m H:i') }}</div>
                  @endif
                  @if(!empty($item['date_fin']))
                    <div><i class="fa-solid fa-stop text-danger me-1"></i>{{ $item['date_fin']->format('d/m H:i') }}</div>
                  @endif
                </td>
                <td>
                  @php $conf = $item['confidence'] ?? 0; @endphp
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px;border-radius:3px;width:60px;">
                      <div class="progress-bar {{ $conf >= 80 ? 'bg-success' : ($conf >= 60 ? 'bg-warning' : 'bg-danger') }}"
                           style="width:{{ $conf }}%"></div>
                    </div>
                    <span class="small fw-semibold">{{ $conf }}%</span>
                  </div>
                </td>
                <td>
                  @php
                    $alreadyImported = !empty($item['steg_ref']) && in_array($item['steg_ref'], $importedRefs);
                  @endphp
                  @if($alreadyImported)
                    <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Oui</span>
                  @else
                    <span class="badge bg-light text-muted border">Non</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>

{{-- ── COUPURES STEG DÉJÀ IMPORTÉES EN BASE ── --}}
<div class="m-card" style="border-radius:14px;">
  <div class="m-card__header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fa-solid fa-database me-2 text-success"></i>Coupures STEG en base HeatAlert</h5>
    <span class="badge bg-success">{{ $stegCount }} importées</span>
  </div>
  <div class="m-card__body p-0">
    @if($stegCoupures->isEmpty())
      <div class="text-center py-4 text-muted">
        <i class="fa-solid fa-inbox fa-2x mb-2 opacity-30"></i>
        <p class="mb-0">Aucune coupure STEG importée en base pour le moment.</p>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Zone</th>
              <th>Type</th>
              <th>Statut</th>
              <th>Début</th>
              <th>Fin</th>
              <th>Réf. STEG</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($stegCoupures as $c)
              <tr>
                <td>
                  <span class="fw-semibold">{{ $c->zone->nom ?? '—' }}</span>
                  <div class="text-muted small">{{ $c->zone->gouvernorat ?? '' }}</div>
                </td>
                <td><span class="badge bg-warning text-dark">{{ ucfirst($c->type) }}</span></td>
                <td>
                  @php
                    $statutClasses = ['prevue' => 'info', 'en_cours' => 'success', 'terminee' => 'secondary'];
                  @endphp
                  <span class="badge bg-{{ $statutClasses[$c->statut] ?? 'secondary' }}">{{ ucfirst($c->statut) }}</span>
                </td>
                <td class="small">{{ $c->date_debut?->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="small">{{ $c->date_fin?->format('d/m/Y H:i') ?? '—' }}</td>
                <td>
                  <code class="small text-muted" title="{{ $c->steg_ref }}">
                    {{ Str::limit($c->steg_ref ?? '—', 20) }}
                  </code>
                </td>
                <td>
                  <a href="{{ route('admin.coupures.show', $c) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-eye"></i>
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="p-3">
        {{ $stegCoupures->links() }}
      </div>
    @endif
  </div>
</div>

{{-- ── INFO SOURCES ── --}}
<div class="m-card mt-4 p-3" style="border-radius:12px;background:#fffbeb;border:1px solid #fef3c7;">
  <h6 class="fw-bold text-warning mb-2"><i class="fa-solid fa-circle-info me-2"></i>Sources de données STEG</h6>
  <div class="row g-2 small text-muted">
    <div class="col-md-4">
      <strong class="text-dark">🔴 RSS</strong><br>
      <a href="https://www.steg.com.tn/fr/rss.xml" target="_blank">steg.com.tn/fr/rss.xml</a><br>
      Flux RSS général — articles filtrés sur les coupures
    </div>
    <div class="col-md-4">
      <strong class="text-dark">🟡 HTML</strong><br>
      <a href="https://www.steg.com.tn/fr/interruptions-de-service" target="_blank">Interruptions de service</a><br>
      Page dédiée aux interruptions programmées
    </div>
    <div class="col-md-4">
      <strong class="text-dark">🟢 Démo</strong><br>
      Données simulées réalistes<br>
      Utilisées quand le site STEG est inaccessible
    </div>
  </div>
  <hr class="my-2">
  <div class="small text-muted">
    ⏱️ Synchronisation automatique : <strong>toutes les 2 heures</strong> via le planificateur Laravel.
    {{-- Pour forcer : <code>php artisan steg:sync-coupures --fresh</code> --}}
  </div>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('syncForm').addEventListener('submit', function() {
  const btn = document.getElementById('syncBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-rotate fa-spin me-1"></i> Synchronisation…';
});
</script>
@endpush

