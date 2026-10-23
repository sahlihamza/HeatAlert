@extends('layouts.admin.admin')

@section('title', 'Détails du signalement')

@section('content')
<div class="page-header">
  <div>
    <nav aria-label="breadcrumb" class="mb-2">
      <a href="{{ route('admin.signalements.index') }}" class="text-decoration-none text-muted" style="font-size:.85rem;">
        <i class="fa-solid fa-arrow-left me-1"></i>Retour aux signalements
      </a>
    </nav>
    <h1><i class="fa-solid fa-flag me-2 text-danger"></i>Détails du signalement</h1>
    <p class="subtitle">Signalé le {{ $signalement->date_signalement?->format('d/m/Y à H:i') }}</p>
  </div>
  <div class="page-header__actions">
    @if($signalement->statut_validation !== 'valide')
      <button type="button" class="m-btn m-btn--primary js-trigger-moderation"
              data-action="{{ route('admin.signalements.valider', $signalement) }}"
              data-title="Valider le signalement"
              data-message="Voulez-vous valider ce signalement ? Il sera considéré comme confirmé."
              data-submit="Valider"
              data-icon="fa-check"
              data-variant="btn-success"
              data-color="success">
        <i class="fa-solid fa-check"></i> Valider
      </button>
    @endif
    @if($signalement->statut_validation !== 'rejete')
      <button type="button" class="m-btn m-btn--ghost js-trigger-moderation"
              style="border:1px solid #dc2626;color:#dc2626;"
              data-action="{{ route('admin.signalements.rejeter', $signalement) }}"
              data-title="Rejeter le signalement"
              data-message="Voulez-vous rejeter ce signalement ? Cette décision pourra être modifiée depuis la modération."
              data-submit="Rejeter"
              data-icon="fa-xmark"
              data-variant="btn-danger"
              data-color="danger">
        <i class="fa-solid fa-xmark"></i> Rejeter
      </button>
    @endif
    <button type="button" class="m-btn m-btn--ghost js-trigger-delete"
            style="border:1px solid #dc2626;color:#dc2626;"
            data-action="{{ route('admin.signalements.destroy', $signalement) }}"
            data-name="Signalement de {{ $signalement->user?->prenom }} {{ $signalement->user?->nom }}"
            data-type="le signalement"
            title="Supprimer le signalement">
      <i class="fa-solid fa-trash"></i> Supprimer
    </button>
  </div>
</div>

@php
  $analysisTone = match($anomalyAnalysis['level']) {
    'high' => 'danger', 'medium' => 'warning', 'unavailable' => 'secondary', default => 'success',
  };
  $analysisBadgeClass = $analysisTone === 'warning' ? 'bg-warning text-dark' : 'bg-' . $analysisTone;
@endphp
<section class="m-card mb-4 border-start border-4 border-{{ $analysisTone }}">
  <div class="m-card__header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h2 class="m-card__title"><i class="fa-solid fa-shield-halved me-2 text-primary"></i>Analyse automatique des anomalies</h2>
    <span class="badge {{ $analysisBadgeClass }}">
      @if($anomalyAnalysis['score'] !== null)Score {{ $anomalyAnalysis['score'] }}/100 · @endif{{ $anomalyAnalysis['label'] }}
    </span>
  </div>
  <div class="p-3">
    <ul class="mb-2">
      @foreach($anomalyAnalysis['reasons'] as $reason)
        <li>{{ $reason }}</li>
      @endforeach
    </ul>
    <p class="small text-muted mb-0">Ce score aide à prioriser la modération. Il ne confirme pas qu’un signalement est faux et ne remplace pas la vérification de l’administrateur.</p>
  </div>
</section>

<div class="row g-4">
  <div class="col-lg-7">
    <section class="m-card">
      <header class="m-card__header d-flex justify-content-between align-items-center">
        <h2 class="m-card__title">Signalement</h2>
        @php
          $statutClass = match($signalement->statut_validation) {
            'valide' => 'bg-success',
            'rejete' => 'bg-danger',
            default => 'bg-warning text-dark',
          };
        @endphp
        <span class="badge {{ $statutClass }}">{{ ucfirst(str_replace('_', ' ', $signalement->statut_validation)) }}</span>
      </header>
      <div class="p-3">
        <dl class="row mb-0">
          <dt class="col-sm-4 text-muted">Auteur</dt>
          <dd class="col-sm-8">
            {{ $signalement->user?->prenom }} {{ $signalement->user?->nom }}
            <small class="d-block text-muted">{{ $signalement->user?->email }}</small>
          </dd>
          <dt class="col-sm-4 text-muted">Date</dt>
          <dd class="col-sm-8">{{ $signalement->date_signalement?->format('d/m/Y H:i') }}</dd>
          <dt class="col-sm-4 text-muted">Description</dt>
          <dd class="col-sm-8" style="white-space:pre-line;">{{ $signalement->description }}</dd>
        </dl>

        @if($signalement->photo_url)
          <div class="mt-4">
            <h3 class="h6 fw-bold">Photo jointe</h3>
            <img src="{{ $signalement->photo_url }}"
                 alt="Photo du signalement"
                 class="img-fluid rounded-3 border"
                 style="max-height:320px;object-fit:contain;">
          </div>
        @endif
      </div>
    </section>
  </div>

  <div class="col-lg-5">
    <section class="m-card">
      <header class="m-card__header">
        <h2 class="m-card__title">Coupure liée</h2>
      </header>
      <div class="p-3">
        @if($signalement->coupure)
          <dl class="row mb-3">
            <dt class="col-sm-5 text-muted">Type</dt>
            <dd class="col-sm-7">{{ ucfirst($signalement->coupure->type) }}</dd>
            <dt class="col-sm-5 text-muted">Zone</dt>
            <dd class="col-sm-7">{{ $signalement->coupure->zone?->nom ?? '—' }}</dd>
            <dt class="col-sm-5 text-muted">Début</dt>
            <dd class="col-sm-7">{{ $signalement->coupure->date_debut?->format('d/m/Y H:i') }}</dd>
            <dt class="col-sm-5 text-muted">Statut</dt>
            <dd class="col-sm-7">{{ ucfirst(str_replace('_', ' ', $signalement->coupure->statut)) }}</dd>
          </dl>
          <a href="{{ route('admin.coupures.show', $signalement->coupure) }}" class="m-btn m-btn--ghost w-100 text-center">
            <i class="fa-solid fa-bolt me-1"></i>Voir la coupure
          </a>
        @else
          <div class="text-center py-3 text-muted">
            <i class="fa-solid fa-link-slash fa-2x mb-2 d-block"></i>
            Aucun lien avec une coupure officielle.
          </div>
        @endif
      </div>
    </section>
  </div>
</div>

@endsection
