@extends('layouts.admin.admin')

@section('title', 'Coupures électriques')

@section('content')
<div class="page-header">
  <div>
    <h1><i class="fa-solid fa-bolt me-2 text-warning"></i>Gestion des Coupures</h1>
    <p class="subtitle">{{ $coupures->total() }} coupure(s) enregistrée(s).</p>
  </div>
  <div class="page-header__actions d-flex gap-2">
    <a href="{{ route('admin.steg.index') }}" class="m-btn m-btn--warning" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; border:none; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);">
      <i class="fa-solid fa-plug-circle-bolt me-1"></i> Sync STEG Officielle
    </a>
    <a href="{{ route('admin.coupures.create') }}" class="m-btn m-btn--primary">
      <i class="fa-solid fa-plus"></i> Nouvelle coupure
    </a>
  </div>
</div>

{{-- ── BARRE DE RECHERCHE AVANCÉE DYNAMIQUE EN 1 CLIC ── --}}
<div class="m-card mb-4 p-3" style="border-radius:14px;background:#ffffff;box-shadow:0 2px 10px rgba(0,0,0,0.03);">
  <form action="{{ route('admin.coupures.index') }}" method="GET" id="coupures-search-form">
    <div class="row g-2 align-items-center mb-3">
      {{-- Input recherche texte avec bouton cliquable --}}
      <div class="col-lg-5 col-md-12">
        <div class="input-group" style="border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
          <span class="input-group-text bg-white border-end-0 text-muted" style="border-color:#e2e8f0;padding-left:14px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </span>
          <input type="text"
                 name="search"
                 id="coupures-live-search"
                 class="form-control border-start-0 border-end-0 py-2 ps-1"
                 style="border-color:#e2e8f0;font-size:14px;"
                 placeholder="Rechercher par type, cause, zone, ville… (frappe instantanée)"
                 value="{{ request('search') }}"
                 autocomplete="off">
          @if(request('search'))
            <button type="button" class="btn btn-outline-secondary border-start-0 border-end-0 bg-white" onclick="document.getElementById('coupures-live-search').value=''; document.getElementById('coupures-search-form').submit();" title="Effacer la recherche">
              ✕
            </button>
          @endif
          <button type="submit" class="btn btn-warning px-3 fw-semibold text-dark" style="border-color:#f59e0b;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="me-1" style="vertical-align:-1px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            Rechercher
          </button>
        </div>
      </div>

      {{-- Sélection Zone --}}
      <div class="col-lg-3 col-md-5">
        <select name="zone_id" id="coupures-zone-select" class="form-select" style="border-color:#e2e8f0;font-size:13.5px;border-radius:10px;" onchange="document.getElementById('coupures-search-form').submit();">
          <option value="">— Toutes les zones —</option>
          @foreach($zones as $z)
            <option value="{{ $z->id }}" {{ request('zone_id') == $z->id ? 'selected' : '' }}>
              {{ $z->nom }} ({{ $z->ville }})
            </option>
          @endforeach
        </select>
      </div>

      {{-- Trier par --}}
      <div class="col-lg-2 col-md-4">
        <select name="tri" id="coupures-tri-select" class="form-select" style="border-color:#e2e8f0;font-size:13.5px;border-radius:10px;" onchange="document.getElementById('coupures-search-form').submit();">
          <option value="priorite" {{ request('tri', 'priorite') === 'priorite' ? 'selected' : '' }}>Priorité métier</option>
          <option value="date_debut_desc" {{ request('tri') === 'date_debut_desc' ? 'selected' : '' }}>Début : plus récente</option>
          <option value="date_debut_asc" {{ request('tri') === 'date_debut_asc' ? 'selected' : '' }}>Début : plus ancienne</option>
          <option value="signalements_desc" {{ request('tri') === 'signalements_desc' ? 'selected' : '' }}>Plus de signalements</option>
        </select>
      </div>

      {{-- Bouton reset --}}
      <div class="col-lg-2 col-md-3 d-flex justify-content-lg-end">
        @if(request()->anyFilled(['search', 'type', 'statut', 'zone_id', 'tri']))
          <a href="{{ route('admin.coupures.index') }}" class="btn btn-outline-warning text-dark btn-sm d-inline-flex align-items-center gap-1 w-100 justify-content-center" style="border-radius:10px;height:38px;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
            Réinitialiser
          </a>
        @endif
      </div>
    </div>

    {{-- Champs cachés pour filtres rapides en 1 clic --}}
    <input type="hidden" name="type" id="coupures-type-hidden" value="{{ request('type') }}">
    <input type="hidden" name="statut" id="coupures-statut-hidden" value="{{ request('statut') }}">

    {{-- ── FILTRES RAPIDES PAR TYPE & STATUT EN 1 CLIC ── --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top" style="border-color:#f1f5f9 !important;">
      <div class="d-flex flex-wrap align-items-center gap-1">
        <span class="text-muted small fw-semibold me-1">Type :</span>
        <button type="button" class="btn btn-sm {{ !request('type') ? 'btn-dark' : 'btn-light border text-dark' }}" onclick="selectTypeFilter('')">
          Tous
        </button>
        <button type="button" class="btn btn-sm {{ request('type')==='delestage' ? 'btn-warning text-dark fw-bold' : 'btn-light border text-dark' }}" onclick="selectTypeFilter('delestage')">
          <i class="fa-solid fa-triangle-exclamation me-1 text-warning"></i>Délestage
        </button>
        <button type="button" class="btn btn-sm {{ request('type')==='surcharge' ? 'btn-danger text-white' : 'btn-light border text-dark' }}" onclick="selectTypeFilter('surcharge')">
          <i class="fa-solid fa-fire me-1 text-danger"></i>Surcharge
        </button>
        <button type="button" class="btn btn-sm {{ request('type')==='panne' ? 'btn-secondary text-white' : 'btn-light border text-dark' }}" onclick="selectTypeFilter('panne')">
          <i class="fa-solid fa-wrench me-1"></i>Panne
        </button>
      </div>

      <div class="d-flex flex-wrap align-items-center gap-1">
        <span class="text-muted small fw-semibold me-1">Statut :</span>
        <button type="button" class="btn btn-sm {{ !request('statut') ? 'btn-dark' : 'btn-light border text-dark' }}" onclick="selectStatutFilter('')">
          Tous
        </button>
        <button type="button" class="btn btn-sm {{ request('statut')==='prevue' ? 'btn-warning text-dark fw-bold' : 'btn-light border text-dark' }}" onclick="selectStatutFilter('prevue')">
          <i class="fa-solid fa-clock me-1 text-warning"></i>Prévue
        </button>
        <button type="button" class="btn btn-sm {{ request('statut')==='en_cours' ? 'btn-danger text-white fw-bold' : 'btn-light border text-dark' }}" onclick="selectStatutFilter('en_cours')">
          <i class="fa-solid fa-bolt me-1 text-white"></i>En cours
        </button>
        <button type="button" class="btn btn-sm {{ request('statut')==='terminee' ? 'btn-success text-white' : 'btn-light border text-dark' }}" onclick="selectStatutFilter('terminee')">
          <i class="fa-solid fa-circle-check me-1 text-white"></i>Terminée
        </button>
      </div>

      <div class="small text-muted" id="coupures-results-counter">
        {{ $coupures->total() }} coupure(s)
      </div>
    </div>
  </form>
</div>

<section class="m-card">
  <div class="table-responsive">
    <table class="m-table" id="coupures-table">
      <thead>
        <tr>
          <th>Type</th>
          <th style="text-align:center;">Statut</th>
          <th>Zone</th>
          <th style="text-align:center;">Début</th>
          <th style="text-align:center;">Fin</th>
          <th style="text-align:center;">Signalements</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody id="coupures-table-body">
        @forelse($coupures as $coupure)
        @php
          $statutClass = match($coupure->statut) {
            'prevue' => 'bg-warning text-dark',
            'en_cours' => 'bg-danger',
            'terminee' => 'bg-success',
            default => 'bg-secondary',
          };
        @endphp
        <tr class="coupure-table-row"
            data-type="{{ strtolower($coupure->type ?? '') }}"
            data-cause="{{ strtolower($coupure->cause ?? '') }}"
            data-zone="{{ strtolower(($coupure->zone?->nom ?? '') . ' ' . ($coupure->zone?->ville ?? '') . ' ' . ($coupure->zone?->gouvernorat ?? '')) }}"
            data-statut="{{ $coupure->statut }}">
          <td>
            <strong>{{ ucfirst($coupure->type) }}</strong>
            @if($coupure->cause)
              <small class="d-block text-muted">{{ Str::limit($coupure->cause, 55) }}</small>
            @endif
          </td>
          <td style="text-align:center;"><span class="badge {{ $statutClass }}">{{ ucfirst(str_replace('_', ' ', $coupure->statut)) }}</span></td>
          <td>{{ $coupure->zone?->nom ?? $coupure->zone?->ville ?? '—' }}</td>
          <td style="text-align:center;">{{ $coupure->date_debut?->format('d/m/Y H:i') }}</td>
          <td style="text-align:center;">{{ $coupure->date_fin?->format('d/m/Y H:i') ?? '—' }}</td>
          <td style="text-align:center;"><span class="badge bg-info text-dark">{{ $coupure->signalements_count }}</span></td>
          <td style="text-align:center; white-space:nowrap;">
            <a href="{{ route('admin.coupures.show', $coupure) }}" class="m-btn m-btn--ghost" style="height:28px;padding:0 10px;font-size:12px;color:#2563eb;" title="Voir">
              <i class="fa-solid fa-eye"></i>
            </a>
            <a href="{{ route('admin.coupures.edit', $coupure) }}" class="m-btn m-btn--ghost" style="height:28px;padding:0 10px;font-size:12px;" title="Modifier">
              <i class="fa-solid fa-pen-to-square"></i>
            </a>
            <button type="button" class="m-btn m-btn--ghost js-trigger-delete"
                    style="height:28px;padding:0 10px;font-size:12px;color:#dc3545;"
                    data-action="{{ route('admin.coupures.destroy', $coupure) }}"
                    data-name="Coupure {{ ucfirst($coupure->type) }} - {{ $coupure->zone?->nom ?? 'Zone inconnue' }}"
                    data-type="la coupure"
                    title="Supprimer">
              <i class="fa-solid fa-trash"></i>
            </button>
          </td>
        </tr>
        @empty
        <tr id="coupures-no-records">
          <td colspan="7" class="text-center py-4 text-muted">
            <i class="fa-solid fa-bolt fa-2x mb-2 d-block text-secondary"></i>
            Aucune coupure enregistrée. <a href="{{ route('admin.coupures.create') }}">Créez la première</a>.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ── PAGINATION AVEC ICÔNES CHEVRON SVG ET ÉTAT DÉSACTIVÉ GARANTI ── --}}
  @if($coupures->hasPages())
  <div class="d-flex flex-column align-items-center mt-3 pb-3 border-top pt-3" style="gap:8px;">
    <div class="text-muted" style="font-size:12.5px;">
      Affichage de <strong>{{ $coupures->firstItem() }}</strong> à <strong>{{ $coupures->lastItem() }}</strong> sur <strong>{{ $coupures->total() }}</strong> coupures
    </div>
    <div class="d-flex justify-content-center align-items-center" style="gap:6px;">
      @if(!$coupures->onFirstPage())
        <a href="{{ $coupures->previousPageUrl() }}" class="m-btn m-btn--ghost" style="height:32px;padding:0 12px;font-size:13px;" title="Page précédente">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
      @else
        <span class="m-btn m-btn--ghost disabled" style="height:32px;padding:0 12px;font-size:13px;opacity:0.35;cursor:not-allowed;" title="Page précédente">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;"><path d="M15 18l-6-6 6-6"/></svg>
        </span>
      @endif

      @foreach($coupures->getUrlRange(max(1, $coupures->currentPage() - 2), min($coupures->lastPage(), $coupures->currentPage() + 2)) as $page => $url)
        <a href="{{ $url }}" class="m-btn {{ $page == $coupures->currentPage() ? 'm-btn--primary' : 'm-btn--ghost' }}" style="height:32px;padding:0 12px;font-size:13px;min-width:34px;text-align:center;">
          {{ $page }}
        </a>
      @endforeach

      @if($coupures->hasMorePages())
        <a href="{{ $coupures->nextPageUrl() }}" class="m-btn m-btn--ghost" style="height:32px;padding:0 12px;font-size:13px;" title="Page suivante">
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
// ─── Sélection Type & Statut en 1 clic ──────────────────────────────────────────
function selectTypeFilter(val) {
  document.getElementById('coupures-type-hidden').value = val;
  document.getElementById('coupures-search-form').submit();
}

function selectStatutFilter(val) {
  document.getElementById('coupures-statut-hidden').value = val;
  document.getElementById('coupures-search-form').submit();
}

// ─── Live search instantané dans le tableau (Client-side) ─────────────────────
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('coupures-live-search');
  const rows = document.querySelectorAll('.coupure-table-row');
  const counterEl = document.getElementById('coupures-results-counter');
  const tbody = document.getElementById('coupures-table-body');

  if (searchInput && rows.length > 0) {
    searchInput.addEventListener('input', function() {
      const q = this.value.trim().toLowerCase();
      let count = 0;

      rows.forEach(r => {
        const type = r.getAttribute('data-type') || '';
        const cause = r.getAttribute('data-cause') || '';
        const zone = r.getAttribute('data-zone') || '';
        const statut = r.getAttribute('data-statut') || '';
        const match = type.includes(q) || cause.includes(q) || zone.includes(q) || statut.includes(q);

        if (match) {
          r.style.display = '';
          count++;
        } else {
          r.style.display = 'none';
        }
      });

      if (counterEl) {
        counterEl.textContent = `${count} coupure(s) affichée(s)`;
      }

      let emptyRow = document.getElementById('coupures-live-empty-row');
      if (count === 0) {
        if (!emptyRow) {
          emptyRow = document.createElement('tr');
          emptyRow.id = 'coupures-live-empty-row';
          emptyRow.innerHTML = `<td colspan="7" class="text-center py-4 text-muted"><i class="fa-solid fa-magnifying-glass fa-2x mb-2 d-block text-secondary"></i>Aucune coupure ne correspond à « <em>${q}</em> ».</td>`;
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
