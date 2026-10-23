@extends('layouts.admin.admin')

@section('title', 'Équipements Sensibles')

@section('content')
<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-shield-heart me-2 text-danger"></i>Gestion des Équipements Sensibles</h1>
    <p class="subtitle">{{ $equipements->total() }} équipement(s) sensible(s) enregistré(s).</p>
  </div>
  <div class="page-header__actions">
    <a href="{{ route('admin.equipements-sensibles.create') }}" class="m-btn m-btn--primary">
      <i class="fa-solid fa-plus me-1"></i> Nouvel équipement
    </a>
  </div>
</div>

{{-- ── BARRE DE RECHERCHE AVANCÉE DYNAMIQUE EN 1 CLIC ── --}}
<div class="m-card mb-4 p-3" style="border-radius:14px;background:#ffffff;box-shadow:0 2px 10px rgba(0,0,0,0.03);">
  <form action="{{ route('admin.equipements-sensibles.index') }}" method="GET" id="equipements-search-form">
    <div class="row g-2 align-items-center mb-3">
      {{-- Input recherche texte instantané --}}
      <div class="col-lg-5 col-md-12">
        <div class="input-group" style="border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
          <span class="input-group-text bg-white border-end-0 text-muted" style="border-color:#e2e8f0;padding-left:14px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </span>
          <input type="text"
                 name="search"
                 id="equipements-live-search"
                 class="form-control border-start-0 border-end-0 py-2 ps-1"
                 style="border-color:#e2e8f0;font-size:14px;"
                 placeholder="Rechercher par nom, type, zone, adresse… (frappe instantanée)"
                 value="{{ request('search') }}"
                 autocomplete="off">
          @if(request('search'))
            <button type="button" class="btn btn-outline-secondary border-start-0 border-end-0 bg-white"
                    onclick="document.getElementById('equipements-live-search').value=''; document.getElementById('equipements-search-form').submit();"
                    title="Effacer la recherche">
              ✕
            </button>
          @endif
          <button type="submit" class="btn btn-danger px-3 fw-semibold text-white" style="background:#dc2626;border-color:#dc2626;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1" style="vertical-align:-1px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            Rechercher
          </button>
        </div>
      </div>

      {{-- Filtre Zone --}}
      <div class="col-lg-3 col-md-6">
        <select name="zone_id" id="equipements-zone-select" class="form-select" style="border-color:#e2e8f0;font-size:13.5px;border-radius:10px;" onchange="document.getElementById('equipements-search-form').submit();">
          <option value="">— Toutes les zones —</option>
          @foreach($zones as $z)
            <option value="{{ $z->id }}" {{ request('zone_id') == $z->id ? 'selected' : '' }}>
              {{ $z->nom }}{{ $z->ville ? ' — ' . $z->ville : '' }}{{ $z->gouvernorat ? ' (' . $z->gouvernorat . ')' : '' }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Filtre Type d'équipement --}}
      <div class="col-lg-2 col-md-4">
        <select name="type" id="equipements-type-select" class="form-select" style="border-color:#e2e8f0;font-size:13.5px;border-radius:10px;" onchange="document.getElementById('equipements-search-form').submit();">
          <option value="">— Tous les types —</option>
          @foreach($types as $t)
            @php
              $label = \App\Models\EquipementSensible::TYPE_LABELS[$t] ?? ucfirst(str_replace('_', ' ', $t));
            @endphp
            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>
              {{ $label }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Bouton Réinitialiser --}}
      <div class="col-lg-2 col-md-2 d-flex justify-content-lg-end">
        @if(request()->anyFilled(['search', 'zone_id', 'type', 'niveau_sensibilite', 'actif']))
          <a href="{{ route('admin.equipements-sensibles.index') }}" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1 w-100 justify-content-center" style="border-radius:10px;height:38px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            Réinitialiser
          </a>
        @endif
      </div>
    </div>

    {{-- Champs cachés pour filtres rapides en 1 clic --}}
    <input type="hidden" name="niveau_sensibilite" id="equipements-niveau-hidden" value="{{ request('niveau_sensibilite') }}">
    <input type="hidden" name="actif" id="equipements-actif-hidden" value="{{ request('actif') }}">

    {{-- ── FILTRES RAPIDES PAR NIVEAU & STATUT EN 1 CLIC ── --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top" style="border-color:#f1f5f9 !important;">
      {{-- Pilules Niveau de sensibilité --}}
      <div class="d-flex flex-wrap align-items-center gap-1">
        <span class="text-muted small fw-semibold me-1">Sensibilité :</span>
        <button type="button" class="btn btn-sm {{ !request('niveau_sensibilite') ? 'btn-dark' : 'btn-light border text-dark' }}" onclick="selectNiveauFilter('')">
          Tous
        </button>
        <button type="button" class="btn btn-sm {{ request('niveau_sensibilite')==='eleve' ? 'btn-danger text-white fw-bold' : 'btn-light border text-dark' }}" onclick="selectNiveauFilter('eleve')">
          🔴 Élevé
        </button>
        <button type="button" class="btn btn-sm {{ request('niveau_sensibilite')==='moyen' ? 'btn-warning text-dark fw-bold' : 'btn-light border text-dark' }}" onclick="selectNiveauFilter('moyen')">
          🟠 Moyen
        </button>
        <button type="button" class="btn btn-sm {{ request('niveau_sensibilite')==='faible' ? 'btn-success text-white' : 'btn-light border text-dark' }}" onclick="selectNiveauFilter('faible')">
          🟢 Faible
        </button>
      </div>

      {{-- Pilules Statut --}}
      <div class="d-flex flex-wrap align-items-center gap-1">
        <span class="text-muted small fw-semibold me-1">Statut :</span>
        <button type="button" class="btn btn-sm {{ !request()->has('actif') || request('actif') === '' || request('actif') === null ? 'btn-dark' : 'btn-light border text-dark' }}" onclick="selectStatutFilter('')">
          Tous
        </button>
        <button type="button" class="btn btn-sm {{ request('actif') === '1' ? 'btn-success text-white fw-bold' : 'btn-light border text-dark' }}" onclick="selectStatutFilter('1')">
          <i class="fa-solid fa-circle-check me-1"></i>Actif
        </button>
        <button type="button" class="btn btn-sm {{ request('actif') === '0' ? 'btn-secondary text-white fw-bold' : 'btn-light border text-dark' }}" onclick="selectStatutFilter('0')">
          <i class="fa-solid fa-circle-pause me-1"></i>Inactif
        </button>
      </div>

      <div class="small text-muted" id="equipements-results-counter">
        {{ $equipements->total() }} équipement(s)
      </div>
    </div>
  </form>
</div>

{{-- ── TABLEAU PRINCIPAL ── --}}
<section class="m-card">
  <div class="table-responsive">
    <table class="m-table" id="equipements-table">
      <thead>
        <tr>
          <th>Équipement & Consignes</th>
          <th style="text-align:center;">Type</th>
          <th style="text-align:center;">Zone</th>
          <th>Adresse / Emplacement</th>
          <th style="text-align:center;">Sensibilité</th>
          <th style="text-align:center;">Statut</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody id="equipements-table-body">
        @forelse($equipements as $equipement)
        @php
          $niveauBadge = match($equipement->niveau_sensibilite) {
            'eleve'  => 'bg-danger text-white',
            'moyen'  => 'bg-warning text-dark',
            default  => 'bg-success text-white'
          };
          $typeIcon = match($equipement->type) {
            'frigo'            => 'fa-solid fa-snowflake text-info',
            'medicament'       => 'fa-solid fa-pills text-danger',
            'ventilateur'      => 'fa-solid fa-fan text-primary',
            'appareil_medical' => 'fa-solid fa-heart-pulse text-danger',
            default            => 'fa-solid fa-microchip text-secondary'
          };
        @endphp
        <tr class="equipement-table-row"
            data-nom="{{ strtolower($equipement->nom ?? '') }}"
            data-desc="{{ strtolower($equipement->description ?? '') }}"
            data-type="{{ strtolower($equipement->type ?? '') }}"
            data-type-label="{{ strtolower($equipement->type_label ?? '') }}"
            data-zone="{{ strtolower(($equipement->zone?->nom ?? '') . ' ' . ($equipement->zone?->ville ?? '') . ' ' . ($equipement->zone?->gouvernorat ?? '')) }}"
            data-adresse="{{ strtolower($equipement->adresse ?? '') }}"
            data-niveau="{{ $equipement->niveau_sensibilite }}"
            data-actif="{{ $equipement->actif ? '1' : '0' }}">
          <td>
            <strong>{{ Str::limit($equipement->nom, 45) }}</strong>
            @if($equipement->niveau_sensibilite === 'eleve')
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" title="Risque critique">
                <i class="fa-solid fa-triangle-exclamation"></i> Prioritaire
              </span>
            @endif
            @if($equipement->description)
              <small class="d-block text-muted" style="max-width:320px;">
                {{ Str::limit($equipement->description, 55) }}
              </small>
            @endif
          </td>
          <td style="text-align:center; white-space:nowrap;">
            <span class="badge bg-light text-dark border">
              <i class="{{ $typeIcon }} me-1"></i>{{ $equipement->type_label }}
            </span>
          </td>
          <td style="text-align:center;">
            @if($equipement->zone)
              <span class="fw-semibold text-dark">{{ $equipement->zone->nom }}</span>
              @if($equipement->zone->ville)
                <small class="d-block text-muted">({{ $equipement->zone->ville }})</small>
              @endif
            @else
              <span class="text-muted">—</span>
            @endif
          </td>
          <td>
            @if($equipement->adresse)
              <span title="{{ $equipement->adresse }}">
                <i class="fa-solid fa-map-pin text-muted me-1"></i>{{ Str::limit($equipement->adresse, 35) }}
              </span>
            @else
              <span class="text-muted">—</span>
            @endif
          </td>
          <td style="text-align:center; white-space:nowrap;">
            <span class="badge {{ $niveauBadge }}">
              {{ $equipement->niveau_label }}
            </span>
          </td>
          <td style="text-align:center; white-space:nowrap;">
            @if($equipement->actif)
              <span class="badge bg-success text-white">
                <i class="fa-solid fa-circle-check me-1"></i>Actif
              </span>
            @else
              <span class="badge bg-secondary text-white">
                <i class="fa-solid fa-circle-pause me-1"></i>Inactif
              </span>
            @endif
          </td>
          <td style="text-align:center; white-space:nowrap;">
            <a href="{{ route('admin.equipements-sensibles.show', $equipement) }}"
               class="m-btn m-btn--ghost"
               style="height:28px;padding:0 10px;font-size:12px;color:#2563eb;"
               title="Détails de l'équipement">
              <i class="fa-solid fa-eye"></i>
            </a>
            <a href="{{ route('admin.equipements-sensibles.edit', $equipement) }}"
               class="m-btn m-btn--ghost"
               style="height:28px;padding:0 10px;font-size:12px;"
               title="Modifier l'équipement">
              <i class="fa-solid fa-pen-to-square"></i>
            </a>
            <button type="button"
                    class="m-btn m-btn--ghost js-trigger-delete"
                    style="height:28px;padding:0 10px;font-size:12px;color:#dc3545;"
                    data-action="{{ route('admin.equipements-sensibles.destroy', $equipement) }}"
                    data-name="{{ $equipement->nom }}"
                    data-type="l'équipement"
                    title="Supprimer">
              <i class="fa-solid fa-trash"></i>
            </button>
          </td>
        </tr>
        @empty
        <tr id="equipements-no-records">
          <td colspan="7" class="text-center py-4 text-muted">
            <i class="fa-solid fa-shield-heart fa-2x mb-2 d-block text-secondary"></i>
            Aucun équipement sensible trouvé. <a href="{{ route('admin.equipements-sensibles.create') }}">Ajoutez le premier</a>.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ── PAGINATION AVEC ICÔNES CHEVRON SVG ET ÉTAT DÉSACTIVÉ GARANTI ── --}}
  @if($equipements->hasPages())
  <div class="d-flex flex-column align-items-center mt-3 pb-3 border-top pt-3" style="gap:8px;">
    <div class="text-muted" style="font-size:12.5px;">
      Affichage de <strong>{{ $equipements->firstItem() }}</strong> à <strong>{{ $equipements->lastItem() }}</strong> sur <strong>{{ $equipements->total() }}</strong> équipements
    </div>
    <div class="d-flex justify-content-center align-items-center" style="gap:6px;">
      @if(!$equipements->onFirstPage())
        <a href="{{ $equipements->previousPageUrl() }}" class="m-btn m-btn--ghost" style="height:32px;padding:0 12px;font-size:13px;" title="Page précédente">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
      @else
        <span class="m-btn m-btn--ghost disabled" style="height:32px;padding:0 12px;font-size:13px;opacity:0.35;cursor:not-allowed;" title="Page précédente">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><path d="M15 18l-6-6 6-6"/></svg>
        </span>
      @endif

      @foreach($equipements->getUrlRange(max(1, $equipements->currentPage() - 2), min($equipements->lastPage(), $equipements->currentPage() + 2)) as $page => $url)
        <a href="{{ $url }}" class="m-btn {{ $page == $equipements->currentPage() ? 'm-btn--primary' : 'm-btn--ghost' }}" style="height:32px;padding:0 12px;font-size:13px;min-width:34px;text-align:center;">
          {{ $page }}
        </a>
      @endforeach

      @if($equipements->hasMorePages())
        <a href="{{ $equipements->nextPageUrl() }}" class="m-btn m-btn--ghost" style="height:32px;padding:0 12px;font-size:13px;" title="Page suivante">
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
// ─── Sélection Niveau & Statut en 1 clic ──────────────────────────────────────────
function selectNiveauFilter(val) {
  document.getElementById('equipements-niveau-hidden').value = val;
  document.getElementById('equipements-search-form').submit();
}

function selectStatutFilter(val) {
  document.getElementById('equipements-actif-hidden').value = val;
  document.getElementById('equipements-search-form').submit();
}

// ─── Live search instantané dans le tableau (Client-side) ─────────────────────
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('equipements-live-search');
  const rows = document.querySelectorAll('.equipement-table-row');
  const counterEl = document.getElementById('equipements-results-counter');
  const tbody = document.getElementById('equipements-table-body');

  if (searchInput && rows.length > 0) {
    searchInput.addEventListener('input', function() {
      const q = this.value.trim().toLowerCase();
      let count = 0;

      rows.forEach(r => {
        const nom = r.getAttribute('data-nom') || '';
        const desc = r.getAttribute('data-desc') || '';
        const type = r.getAttribute('data-type') || '';
        const typeLabel = r.getAttribute('data-type-label') || '';
        const zone = r.getAttribute('data-zone') || '';
        const adresse = r.getAttribute('data-adresse') || '';
        const niveau = r.getAttribute('data-niveau') || '';

        const match = nom.includes(q) || desc.includes(q) || type.includes(q) || typeLabel.includes(q) || zone.includes(q) || adresse.includes(q) || niveau.includes(q);

        if (match) {
          r.style.display = '';
          count++;
        } else {
          r.style.display = 'none';
        }
      });

      if (counterEl) {
        counterEl.textContent = `${count} équipement(s) affiché(s)`;
      }

      let emptyRow = document.getElementById('equipements-live-empty-row');
      if (count === 0) {
        if (!emptyRow) {
          emptyRow = document.createElement('tr');
          emptyRow.id = 'equipements-live-empty-row';
          emptyRow.innerHTML = `<td colspan="7" class="text-center py-4 text-muted"><i class="fa-solid fa-magnifying-glass fa-2x mb-2 d-block text-secondary"></i>Aucun équipement ne correspond à « <em>${q}</em> ».</td>`;
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
