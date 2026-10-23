@extends('layouts.front.front')

@section('title', 'Ma préparation personnelle — HeatAlert')
@section('meta_description', 'Déclarez et protégez vos équipements sensibles pendant les périodes de forte chaleur.')

@section('content')
<div class="page-title position-relative overflow-hidden"
     style="background:linear-gradient(135deg,rgba(127,29,29,.91),rgba(220,38,38,.78)),url('{{ asset('assets/front/img/health/facilities-9.webp') }}') center/cover no-repeat;padding-top:135px;padding-bottom:45px;">
  <div class="heading"><div class="container"><div class="row justify-content-center text-center"><div class="col-lg-8">
    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-3 rounded-pill small fw-bold text-white" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);"><i class="bi bi-shield-heart"></i><span>Espace Citoyen & Prévention</span></div>
    <h1 class="heading-title text-white" style="font-weight:800;font-size:36px;"><i class="bi bi-shield-heart me-2"></i>Ma préparation personnelle</h1>
    <p class="mb-0 text-white" style="font-size:16px;line-height:1.6;">Déclarez les équipements importants à protéger pendant la canicule ou une coupure.</p>
  </div></div></div></div>
  <nav class="breadcrumbs mt-4" style="background:rgba(0,0,0,.3);border-top:1px solid rgba(255,255,255,.15);"><div class="container"><ol class="mb-0"><li><a href="{{ route('front.home') }}" class="text-white-50">Accueil</a></li><li class="current text-white fw-semibold">Ma préparation personnelle</li></ol></div></nav>
</div>

<section class="py-5" style="background:#f8fafc;min-height:65vh;"><div class="container">
  @foreach(['success','deleted'] as $type)
    @if(session($type))<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3"><i class="bi bi-check-circle me-2"></i>{{ session($type) }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button></div>@endif
  @endforeach
  @if(session('risk_warning'))<div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-3"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('risk_warning') }} Vérifiez que le service ML est démarré et que la migration est exécutée.<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button></div>@endif
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h2 class="h4 mb-1">Mes équipements sensibles</h2><p class="text-muted mb-0"><strong id="equipementsCount">{{ $equipements->count() }}</strong> équipement(s) affiché(s) <span class="small">sur {{ $equipements->total() }}</span></p></div>
    <button type="button" class="btn btn-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addEquipementModal"><i class="bi bi-plus-lg me-1"></i>Ajouter un équipement</button>
  </div>
  <div class="card border-0 shadow-sm rounded-4 mb-4"><div class="card-body p-3 p-md-4">
    <form id="equipementsFilters" method="GET" action="{{ route('front.equipements_sensibles.index') }}"><div class="row g-3 align-items-end">
      <div class="col-lg-6"><label class="form-label small fw-semibold text-muted" for="equipementsSearch">Recherche instantanée</label><div class="input-group"><span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-danger"></i></span><input id="equipementsSearch" name="search" class="form-control bg-light border-start-0" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, type ou description" autocomplete="off"></div></div>
      <div class="col-md-4 col-lg-3"><label class="form-label small fw-semibold text-muted" for="equipementsNiveau">Niveau de risque</label><select id="equipementsNiveau" name="niveau_sensibilite" class="form-select"><option value="">Tous les niveaux</option>@foreach($niveaux as $niveau)<option value="{{ $niveau }}" @selected(($filters['niveau_sensibilite'] ?? '') === $niveau)>{{ ucfirst($niveau) }}</option>@endforeach</select></div>
      <div class="col-md-4 col-lg-2"><label class="form-label small fw-semibold text-muted" for="equipementsActif">Statut</label><select id="equipementsActif" name="actif" class="form-select"><option value="">Tous</option><option value="1" @selected(($filters['actif'] ?? '') === '1')>À surveiller</option><option value="0" @selected(($filters['actif'] ?? '') === '0')>Désactivés</option></select></div>
      <div class="col-md-4 col-lg-1"><button id="resetEquipementsFilters" type="button" class="btn btn-outline-danger w-100" title="Réinitialiser les filtres"><i class="bi bi-arrow-counterclockwise"></i></button></div>
    </div></form>
  </div></div>
  <div id="equipementsGrid" class="row g-4">@forelse($equipements as $equipement)
    <div class="col-md-6 col-lg-4 equipement-card" data-equipement-card data-search="{{ strtolower($equipement->nom . ' ' . $equipement->type . ' ' . ($equipement->description ?? '')) }}" data-niveau="{{ $equipement->niveau_sensibilite }}" data-actif="{{ $equipement->actif ? '1' : '0' }}"><div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="card-body p-4"><div class="d-flex justify-content-between align-items-start gap-2"><h3 class="h5 fw-bold mb-2">{{ $equipement->nom }}</h3><span class="badge rounded-pill bg-{{ $equipement->niveau_sensibilite === 'eleve' ? 'danger' : ($equipement->niveau_sensibilite === 'moyen' ? 'warning text-dark' : 'success') }}">{{ ucfirst($equipement->niveau_sensibilite) }}</span></div>
      <div class="text-danger small fw-semibold mb-3"><i class="bi bi-cpu me-1"></i>{{ $equipement->type }}</div><p class="text-muted small mb-3">{{ $equipement->description ?: 'Aucune précaution renseignée.' }}</p><span class="badge rounded-pill bg-{{ $equipement->actif ? 'success' : 'secondary' }}"><i class="bi bi-{{ $equipement->actif ? 'eye' : 'eye-slash' }} me-1"></i>{{ $equipement->actif ? 'À surveiller' : 'Désactivé' }}</span>
      @if($equipement->latestRiskPrediction)
        @php($prediction = $equipement->latestRiskPrediction)
        <div class="mt-3 p-3 rounded-3 bg-light border">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small fw-semibold"><i class="bi bi-graph-up-arrow me-1"></i>Risque dans 24 h</span>
            <span class="badge rounded-pill bg-{{ $prediction->risk_level === 'high' ? 'danger' : ($prediction->risk_level === 'medium' ? 'warning text-dark' : 'success') }}">{{ strtoupper($prediction->risk_level) }}</span>
          </div>
          <div class="fw-bold">{{ number_format($prediction->probability * 100, 1, ',', ' ') }} %</div>
          <div class="small text-muted">{{ $prediction->target_window_start?->format('d/m/Y H:i') }}</div>
          @if($prediction->factors)
            <div class="small text-muted mt-2">{{ implode(' · ', $prediction->factors) }}</div>
          @endif
        </div>
      @else
        <div class="mt-3 small text-muted"><i class="bi bi-hourglass-split me-1"></i>Aucune prédiction disponible.</div>
      @endif
      </div>
      <div class="card-footer bg-white border-0 px-4 pb-4 d-flex gap-2"><button type="button" class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1" data-bs-toggle="modal" data-bs-target="#editEquipementModal{{ $equipement->id }}"><i class="bi bi-pencil me-1"></i>Modifier</button><form method="POST" action="{{ route('front.equipements_sensibles.destroy', $equipement) }}" onsubmit="return confirm('Supprimer cet équipement ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger rounded-pill" title="Supprimer"><i class="bi bi-trash"></i></button></form></div>
    </div></div>
  @empty<div class="col-12"><div class="card border-0 shadow-sm rounded-4 text-center p-5"><i class="bi bi-shield-plus text-danger" style="font-size:3rem"></i><h3 class="h5 mt-3">Aucun équipement enregistré</h3><p class="text-muted">Votre liste est vide. Ajoutez un équipement important pour mieux anticiper la canicule.</p><button type="button" class="btn btn-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addEquipementModal">Ajouter mon premier équipement</button></div></div>@endforelse</div>
  <div class="mt-4 d-flex justify-content-center">{{ $equipements->links() }}</div>
</div></section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('equipementsFilters');
  const search = document.getElementById('equipementsSearch');
  const niveau = document.getElementById('equipementsNiveau');
  const actif = document.getElementById('equipementsActif');
  const reset = document.getElementById('resetEquipementsFilters');
  const count = document.getElementById('equipementsCount');
  const cards = Array.from(document.querySelectorAll('[data-equipement-card]'));
  const grid = document.getElementById('equipementsGrid');
  let emptyMessage = null;

  function filterCards() {
    const term = search.value.trim().toLowerCase();
    const selectedNiveau = niveau.value;
    const selectedActif = actif.value;
    let visible = 0;

    cards.forEach(function (card) {
      const matches = (!term || card.dataset.search.includes(term))
        && (!selectedNiveau || card.dataset.niveau === selectedNiveau)
        && (!selectedActif || card.dataset.actif === selectedActif);
      card.classList.toggle('d-none', !matches);
      if (matches) visible++;
    });

    count.textContent = visible;
    if (emptyMessage) emptyMessage.remove();
    if (visible === 0 && cards.length > 0) {
      emptyMessage = document.createElement('div');
      emptyMessage.className = 'col-12';
      emptyMessage.innerHTML = '<div class="alert alert-light border text-center text-muted mb-0"><i class="bi bi-search me-2"></i>Aucun équipement ne correspond aux critères sélectionnés.</div>';
      grid.appendChild(emptyMessage);
    }
  }

  [search, niveau, actif].forEach(function (control) {
    control.addEventListener(control === search ? 'input' : 'change', filterCards);
  });
  reset.addEventListener('click', function () {
    search.value = '';
    niveau.value = '';
    actif.value = '';
    filterCards();
  });
  form.addEventListener('submit', function (event) {
    event.preventDefault();
    filterCards();
  });
  filterCards();
});
</script>
@endpush

<div class="modal fade" id="addEquipementModal" tabindex="-1" aria-labelledby="addEquipementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header bg-danger text-white">
        <h2 class="modal-title h5" id="addEquipementModalLabel"><i class="bi bi-shield-plus me-2"></i>Ajouter un équipement sensible</h2>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <form method="POST" action="{{ route('front.equipements_sensibles.store') }}" class="js-front-equipement-form" novalidate>
        @csrf
        <input type="hidden" name="_modal" value="create">
        <div class="modal-body p-4">@include('front.equipements_sensibles._form', ['equipement' => null])</div>
      </form>
    </div>
  </div>
</div>

@foreach($equipements as $equipement)
<div class="modal fade" id="editEquipementModal{{ $equipement->id }}" tabindex="-1" aria-labelledby="editEquipementModalLabel{{ $equipement->id }}" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 shadow">
      <div class="modal-header bg-primary text-white">
        <h2 class="modal-title h5" id="editEquipementModalLabel{{ $equipement->id }}"><i class="bi bi-pencil-square me-2"></i>Modifier l’équipement</h2>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <form method="POST" action="{{ route('front.equipements_sensibles.update', $equipement) }}" class="js-front-equipement-form" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="_modal" value="edit-{{ $equipement->id }}">
        <div class="modal-body p-4">@include('front.equipements_sensibles._form', ['equipement' => $equipement])</div>
      </form>
    </div>
  </div>
</div>
@endforeach

@endsection
