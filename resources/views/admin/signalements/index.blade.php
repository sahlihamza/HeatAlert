@extends('layouts.admin.admin')

@section('title', 'Signalements de coupures')

@section('content')
<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-flag me-2 text-danger"></i>Modération des signalements</h1>
    <p class="subtitle">{{ $signalements->total() }} signalement(s) citoyen(s) enregistré(s).</p>
  </div>
</div>

{{-- ── BARRE DE RECHERCHE AVANCÉE DYNAMIQUE EN 1 CLIC ── --}}
<div class="m-card mb-4 p-3" style="border-radius:14px;background:#ffffff;box-shadow:0 2px 10px rgba(0,0,0,0.03);">
  <form action="{{ route('admin.signalements.index') }}" method="GET" id="signalements-search-form">
    <div class="row g-2 align-items-center mb-3">
      {{-- Input recherche texte avec bouton cliquable --}}
      <div class="col-lg-6 col-md-12">
        <div class="input-group" style="border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
          <span class="input-group-text bg-white border-end-0 text-muted" style="border-color:#e2e8f0;padding-left:14px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </span>
          <input type="text"
                 name="search"
                 id="signalements-live-search"
                 class="form-control border-start-0 border-end-0 py-2 ps-1"
                 style="border-color:#e2e8f0;font-size:14px;"
                 placeholder="Rechercher par auteur, email, description, zone… (frappe instantanée)"
                 value="{{ request('search') }}"
                 autocomplete="off">
          @if(request('search'))
            <button type="button" class="btn btn-outline-secondary border-start-0 border-end-0 bg-white" onclick="document.getElementById('signalements-live-search').value=''; document.getElementById('signalements-search-form').submit();" title="Effacer la recherche">
              ✕
            </button>
          @endif
          <button type="submit" class="btn btn-danger px-3 fw-semibold text-white" style="background:#dc2626;border-color:#dc2626;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1" style="vertical-align:-1px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            Rechercher
          </button>
        </div>
      </div>

      {{-- Sélection Zone --}}
      <div class="col-lg-4 col-md-8">
        <select name="zone_id" id="signalements-zone-select" class="form-select" style="border-color:#e2e8f0;font-size:13.5px;border-radius:10px;" onchange="document.getElementById('signalements-search-form').submit();">
          <option value="">— Toutes les zones —</option>
          @foreach($zones as $z)
            <option value="{{ $z->id }}" {{ request('zone_id') == $z->id ? 'selected' : '' }}>
              {{ $z->nom }} ({{ $z->ville }})
            </option>
          @endforeach
        </select>
      </div>

      {{-- Bouton reset --}}
      <div class="col-lg-2 col-md-4 d-flex justify-content-lg-end">
        @if(request()->anyFilled(['search', 'statut_validation', 'zone_id']))
          <a href="{{ route('admin.signalements.index') }}" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1 w-100 justify-content-center" style="border-radius:10px;height:38px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            Réinitialiser
          </a>
        @endif
      </div>
    </div>

    {{-- Champ caché pour filtre rapide de statut en 1 clic --}}
    <input type="hidden" name="statut_validation" id="signalements-statut-hidden" value="{{ request('statut_validation') }}">

    {{-- ── FILTRES RAPIDES PAR STATUT EN 1 CLIC ── --}}
    <div class="d-flex flex-wrap align-items-center gap-2 pt-2 border-top" style="border-color:#f1f5f9 !important;">
      <span class="text-muted small fw-semibold me-1">Statut :</span>
      <button type="button" class="btn btn-sm {{ !request('statut_validation') ? 'btn-dark' : 'btn-light border text-dark' }}" onclick="selectStatutFilter('')">
        Tous
      </button>
      <button type="button" class="btn btn-sm {{ request('statut_validation')==='en_attente' ? 'btn-warning text-dark fw-bold' : 'btn-light border text-dark' }}" onclick="selectStatutFilter('en_attente')">
        <i class="fa-solid fa-clock me-1 text-warning"></i>En attente de validation
      </button>
      <button type="button" class="btn btn-sm {{ request('statut_validation')==='valide' ? 'btn-success text-white' : 'btn-light border text-dark' }}" onclick="selectStatutFilter('valide')">
        <i class="fa-solid fa-circle-check me-1 text-success"></i>Validés
      </button>
      <button type="button" class="btn btn-sm {{ request('statut_validation')==='rejete' ? 'btn-danger text-white' : 'btn-light border text-dark' }}" onclick="selectStatutFilter('rejete')">
        <i class="fa-solid fa-circle-xmark me-1 text-danger"></i>Rejetés
      </button>

      <div class="ms-auto small text-muted d-none d-md-block" id="signalements-results-counter">
        {{ $signalements->total() }} signalement(s)
      </div>
    </div>
  </form>
</div>

<section class="m-card">
  <div class="table-responsive">
    <table class="m-table" id="signalements-table">
      <thead>
        <tr>
          <th>Auteur</th>
          <th>Coupure liée</th>
          <th>Description</th>
          <th>Analyse automatique</th>
          <th style="text-align:center;">Photo</th>
          <th style="text-align:center;">Statut</th>
          <th style="text-align:center;">Date</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody id="signalements-table-body">
        @forelse($signalements as $signalement)
          @php
            $statutClass = match($signalement->statut_validation) {
              'valide' => 'bg-success',
              'rejete' => 'bg-danger',
              default => 'bg-warning text-dark',
            };
          @endphp
          <tr class="signalement-table-row" data-author="{{ strtolower(($signalement->user?->prenom ?? '') . ' ' . ($signalement->user?->nom ?? '') . ' ' . ($signalement->user?->email ?? '')) }}" data-desc="{{ strtolower($signalement->description ?? '') }}" data-zone="{{ strtolower($signalement->coupure?->zone?->nom ?? '') }}" data-statut="{{ $signalement->statut_validation }}">
            <td>
              <strong>{{ $signalement->user?->prenom }} {{ $signalement->user?->nom }}</strong>
              <small class="d-block text-muted">{{ $signalement->user?->email ?? 'Utilisateur inconnu' }}</small>
            </td>
            <td>
              @if($signalement->coupure)
                <a href="{{ route('admin.coupures.show', $signalement->coupure) }}" class="text-decoration-none fw-semibold">
                  {{ ucfirst($signalement->coupure->type) }}
                </a>
                <small class="d-block text-muted">{{ $signalement->coupure->zone?->nom ?? 'Zone inconnue' }}</small>
              @else
                <span class="text-muted">Sans coupure officielle</span>
              @endif
            </td>
            <td>{{ Str::limit($signalement->description, 75) }}</td>
            <td style="min-width:190px;">
              @php $analysis = $signalement->anomaly_analysis; @endphp
              @php
                $analysisClass = match($analysis['level']) {
                  'high' => 'bg-danger-subtle text-danger border border-danger-subtle',
                  'medium' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                  'unavailable' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                  default => 'bg-success-subtle text-success border border-success-subtle',
                };
              @endphp
              <span class="badge {{ $analysisClass }}" title="{{ implode(' · ', $analysis['reasons']) }}">
                @if($analysis['score'] !== null){{ $analysis['score'] }}/100 · @endif{{ $analysis['label'] }}
              </span>
              @foreach($analysis['reasons'] as $reason)
                <small class="d-block text-muted mt-1">{{ $reason }}</small>
              @endforeach
            </td>
            <td style="text-align:center;">
              @if($signalement->photo_url)
                <img src="{{ $signalement->photo_url }}"
                     alt="Photo du signalement"
                     style="width:48px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td style="text-align:center;"><span class="badge {{ $statutClass }}">{{ ucfirst(str_replace('_', ' ', $signalement->statut_validation)) }}</span></td>
            <td style="text-align:center;">{{ $signalement->date_signalement?->format('d/m/Y H:i') }}</td>
            <td style="text-align:center;white-space:nowrap;">
              <a href="{{ route('admin.signalements.show', $signalement) }}"
                 class="m-btn m-btn--ghost" style="height:28px;padding:0 10px;font-size:12px;color:#2563eb;" title="Voir">
                <i class="fa-solid fa-eye"></i>
              </a>
              @if($signalement->statut_validation !== 'valide')
                <button type="button" class="m-btn m-btn--ghost js-trigger-moderation"
                        style="height:28px;padding:0 10px;font-size:12px;color:#16a34a;"
                        data-action="{{ route('admin.signalements.valider', $signalement) }}"
                        data-title="Valider le signalement"
                        data-message="Voulez-vous valider ce signalement ? Il sera considéré comme confirmé."
                        data-submit="Valider"
                        data-icon="fa-check"
                        data-variant="btn-success"
                        data-color="success"
                        title="Valider">
                  <i class="fa-solid fa-check"></i>
                </button>
              @endif
              @if($signalement->statut_validation !== 'rejete')
                <button type="button" class="m-btn m-btn--ghost js-trigger-moderation"
                        style="height:28px;padding:0 10px;font-size:12px;color:#dc2626;"
                        data-action="{{ route('admin.signalements.rejeter', $signalement) }}"
                        data-title="Rejeter le signalement"
                        data-message="Voulez-vous rejeter ce signalement ? Cette décision pourra être modifiée depuis la modération."
                        data-submit="Rejeter"
                        data-icon="fa-xmark"
                        data-variant="btn-danger"
                        data-color="danger"
                        title="Rejeter">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              @endif
              <button type="button" class="m-btn m-btn--ghost js-trigger-delete"
                      style="height:28px;padding:0 10px;font-size:12px;color:#dc3545;"
                      data-action="{{ route('admin.signalements.destroy', $signalement) }}"
                      data-name="Signalement de {{ $signalement->user?->prenom }} {{ $signalement->user?->nom }}"
                      data-type="le signalement"
                      title="Supprimer">
                <i class="fa-solid fa-trash"></i>
              </button>
            </td>
          </tr>
        @empty
          <tr id="signalements-no-records">
            <td colspan="8" class="text-center py-4 text-muted">
              <i class="fa-solid fa-flag fa-2x mb-2 d-block text-secondary"></i>
              Aucun signalement trouvé.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ── PAGINATION AVEC ICÔNES CHEVRON SVG ET ÉTAT DÉSACTIVÉ GARANTI ── --}}
  @if($signalements->hasPages())
    <div class="d-flex flex-column align-items-center mt-3 pb-3 border-top pt-3" style="gap:8px;">
      <div class="text-muted" style="font-size:12.5px;">
        Affichage de <strong>{{ $signalements->firstItem() }}</strong> à <strong>{{ $signalements->lastItem() }}</strong> sur <strong>{{ $signalements->total() }}</strong> signalements
      </div>
      <div class="d-flex justify-content-center align-items-center" style="gap:6px;">
        @if(!$signalements->onFirstPage())
          <a href="{{ $signalements->previousPageUrl() }}" class="m-btn m-btn--ghost" style="height:32px;padding:0 12px;font-size:13px;" title="Page précédente">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><path d="M15 18l-6-6 6-6"/></svg>
          </a>
        @else
          <span class="m-btn m-btn--ghost disabled" style="height:32px;padding:0 12px;font-size:13px;opacity:0.35;cursor:not-allowed;" title="Page précédente">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><path d="M15 18l-6-6 6-6"/></svg>
          </span>
        @endif

        @foreach($signalements->getUrlRange(max(1, $signalements->currentPage() - 2), min($signalements->lastPage(), $signalements->currentPage() + 2)) as $page => $url)
          <a href="{{ $url }}" class="m-btn {{ $page == $signalements->currentPage() ? 'm-btn--primary' : 'm-btn--ghost' }}" style="height:32px;padding:0 12px;font-size:13px;min-width:34px;text-align:center;">
            {{ $page }}
          </a>
        @endforeach

        @if($signalements->hasMorePages())
          <a href="{{ $signalements->nextPageUrl() }}" class="m-btn m-btn--ghost" style="height:32px;padding:0 12px;font-size:13px;" title="Page suivante">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><path d="M9 18l6-6-6-6"/></svg>
          </a>
        @else
          <span class="m-btn m-btn--ghost disabled" style="height:32px;padding:0 12px;font-size:13px;opacity:0.35;cursor:not-allowed;" title="Page suivante">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><path d="M9 18l6-6-6-6"/></svg>
          </span>
        @endif
      </div>
    </div>
  @endif
</section>

@push('scripts')
<script>
// ─── Sélection statut en 1 clic ───────────────────────────────────────────────
function selectStatutFilter(val) {
  document.getElementById('signalements-statut-hidden').value = val;
  document.getElementById('signalements-search-form').submit();
}

// ─── Live search instantané dans le tableau (Client-side) ─────────────────────
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('signalements-live-search');
  const rows = document.querySelectorAll('.signalement-table-row');
  const counterEl = document.getElementById('signalements-results-counter');
  const tbody = document.getElementById('signalements-table-body');

  if (searchInput && rows.length > 0) {
    searchInput.addEventListener('input', function() {
      const q = this.value.trim().toLowerCase();
      let count = 0;

      rows.forEach(r => {
        const author = r.getAttribute('data-author') || '';
        const desc = r.getAttribute('data-desc') || '';
        const zone = r.getAttribute('data-zone') || '';
        const match = author.includes(q) || desc.includes(q) || zone.includes(q);

        if (match) {
          r.style.display = '';
          count++;
        } else {
          r.style.display = 'none';
        }
      });

      if (counterEl) {
        counterEl.textContent = `${count} signalement(s) affiché(s)`;
      }

      let emptyRow = document.getElementById('signalements-live-empty-row');
      if (count === 0) {
        if (!emptyRow) {
          emptyRow = document.createElement('tr');
          emptyRow.id = 'signalements-live-empty-row';
          emptyRow.innerHTML = `<td colspan="8" class="text-center py-4 text-muted"><i class="fa-solid fa-magnifying-glass fa-2x mb-2 d-block text-secondary"></i>Aucun signalement ne correspond à « <em>${q}</em> ».</td>`;
          tbody.appendChild(emptyRow);
        } else {
          emptyRow.style.display = '';
          emptyRow.querySelector('em').textContent = q;
        }
      } else if (emptyRow) {
        emptyRow.style.display = 'none';
      }
    });
  }
});
</script>
@endpush
@endsection
