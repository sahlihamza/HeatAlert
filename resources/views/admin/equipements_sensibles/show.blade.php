@extends('layouts.admin.admin')

@section('title', 'Détails de l\'Équipement — ' . $equipement->nom)

@section('content')
<div class="page-header">
  <div>
    <nav aria-label="breadcrumb" class="mb-2">
      <a href="{{ route('admin.equipements-sensibles.index') }}" class="text-decoration-none text-muted" style="font-size:.85rem;">
        <i class="fa-solid fa-arrow-left me-1"></i>Retour à la liste des équipements
      </a>
    </nav>
    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
      <h1><i class="fa-solid fa-shield-heart me-2 text-danger"></i>{{ $equipement->nom }}</h1>
      @php
        $badgeClass = match($equipement->niveau_sensibilite) {
          'eleve'  => 'bg-danger text-white',
          'moyen'  => 'bg-warning text-dark',
          default  => 'bg-success text-white',
        };
      @endphp
      <span class="badge {{ $badgeClass }}" style="font-size:.88rem;padding:6px 12px;">
        Sensibilité {{ $equipement->niveau_label }}
      </span>
      @if($equipement->actif)
        <span class="badge bg-success text-white" style="font-size:.85rem;">
          <i class="fa-solid fa-circle-check me-1"></i>Actif
        </span>
      @else
        <span class="badge bg-secondary text-white" style="font-size:.85rem;">
          <i class="fa-solid fa-circle-pause me-1"></i>Inactif
        </span>
      @endif
    </div>
    <p class="subtitle">
      @if($equipement->zone)
        <i class="fa-solid fa-location-dot me-1 text-danger"></i>Zone :
        <a href="{{ route('admin.zones.show', $equipement->zone) }}" class="text-decoration-none fw-bold text-dark">
          {{ $equipement->zone->nom }}{{ $equipement->zone->ville ? ' (' . $equipement->zone->ville . ')' : '' }}
        </a>
      @else
        <span class="text-muted"><i class="fa-solid fa-globe me-1"></i>Zone non spécifiée</span>
      @endif
      <span class="mx-2">•</span>
      <i class="fa-regular fa-clock me-1"></i>Enregistré le {{ $equipement->created_at?->format('d/m/Y à H:i') ?? '—' }}
    </p>
  </div>
  <div class="page-header__actions">
    <a href="{{ route('admin.equipements-sensibles.edit', $equipement) }}" class="m-btn m-btn--primary">
      <i class="fa-solid fa-pen-to-square"></i> Modifier l'équipement
    </a>
    <button type="button" class="m-btn m-btn--ghost js-trigger-delete" style="border:1px solid #dc2626;color:#dc2626;"
            data-action="{{ route('admin.equipements-sensibles.destroy', $equipement) }}"
            data-name="{{ $equipement->nom }}"
            data-type="l'équipement"
            title="Supprimer l'équipement">
      <i class="fa-solid fa-trash"></i> Supprimer
    </button>
  </div>
</div>

<div class="row g-3">
  {{-- Colonne principale --}}
  <div class="col-lg-8">
    {{-- Fiche d'informations générales --}}
    <section class="m-card mb-3">
      <header class="m-card__header">
        <h2 class="m-card__title">
          <i class="fa-solid fa-circle-info me-2 text-primary"></i>Fiche technique & Emplacement
        </h2>
      </header>
      <div class="p-3">
        <dl class="row mb-0">
          <dt class="col-sm-4 text-muted">Type d'équipement</dt>
          <dd class="col-sm-8 fw-semibold">
            <span class="badge bg-light text-dark border">
              <i class="{{ $equipement->type_icon }} me-1"></i>{{ $equipement->type_label }}
            </span>
          </dd>

          <dt class="col-sm-4 text-muted">Niveau de sensibilité</dt>
          <dd class="col-sm-8">
            <span class="badge {{ $badgeClass }}">{{ $equipement->niveau_label }}</span>
            @if($equipement->niveau_sensibilite === 'eleve')
              <small class="text-danger d-block mt-1">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>Risque critique : notification prioritaire déclenchée dès l'émission d'une alerte météo ou d'une coupure.
              </small>
            @elseif($equipement->niveau_sensibilite === 'moyen')
              <small class="text-warning text-dark d-block mt-1">
                <i class="fa-solid fa-circle-info me-1"></i>Vigilance renforcée en période caniculaire.
              </small>
            @else
              <small class="text-muted d-block mt-1">
                <i class="fa-solid fa-circle-check me-1"></i>Surveillance standard.
              </small>
            @endif
          </dd>

          <dt class="col-sm-4 text-muted">Zone géographique</dt>
          <dd class="col-sm-8">
            @if($equipement->zone)
              <a href="{{ route('admin.zones.show', $equipement->zone) }}" class="text-primary fw-semibold text-decoration-none">
                <i class="fa-solid fa-location-dot me-1"></i>{{ $equipement->zone->nom }} ({{ $equipement->zone->ville ?? $equipement->zone->gouvernorat }})
              </a>
            @else
              <span class="text-muted">—</span>
            @endif
          </dd>

          <dt class="col-sm-4 text-muted">Adresse / Emplacement</dt>
          <dd class="col-sm-8">
            {{ $equipement->adresse ?: 'Non spécifiée' }}
          </dd>

          <dt class="col-sm-4 text-muted">Statut opérationnel</dt>
          <dd class="col-sm-8">
            @if($equipement->actif)
              <span class="badge bg-success text-white"><i class="fa-solid fa-check me-1"></i>Actif</span>
            @else
              <span class="badge bg-secondary text-white"><i class="fa-solid fa-pause me-1"></i>Inactif</span>
            @endif
          </dd>

          <dt class="col-sm-4 text-muted">Utilisateur rattaché</dt>
          <dd class="col-sm-8">
            @if($equipement->user)
              <i class="fa-solid fa-user me-1 text-muted"></i>{{ $equipement->user->name }}
              <small class="text-muted">({{ $equipement->user->email }})</small>
            @else
              <span class="text-muted">Géré par l'administration</span>
            @endif
          </dd>
        </dl>
      </div>
    </section>

    {{-- Consignes de sécurité et description --}}
    <section class="m-card mb-3">
      <header class="m-card__header">
        <h2 class="m-card__title">
          <i class="fa-solid fa-file-lines me-2 text-warning"></i>Consignes d'urgence & Description
        </h2>
      </header>
      <div class="p-3">
        @if($equipement->description)
          <div class="p-3 bg-light rounded-3" style="border-left:4px solid #f59e0b;white-space:pre-line;line-height:1.6;">
            {{ $equipement->description }}
          </div>
        @else
          <p class="text-muted mb-0 fst-italic">
            Aucune consigne spécifique n'a été rédigée pour cet équipement.
          </p>
        @endif
      </div>
    </section>

    {{-- Notifications associées (Module 4) si présentes --}}
    @if($equipement->module4Notifications && $equipement->module4Notifications->isNotEmpty())
    <section class="m-card mb-3">
      <header class="m-card__header d-flex justify-content-between align-items-center">
        <h2 class="m-card__title">
          <i class="fa-solid fa-bell me-2 text-danger"></i>Historique des notifications liées
        </h2>
        <span class="badge bg-danger text-white">{{ $equipement->module4Notifications->count() }}</span>
      </header>
      <div class="table-responsive">
        <table class="m-table">
          <thead>
            <tr>
              <th>Titre / Message</th>
              <th style="text-align:center;">Canal</th>
              <th style="text-align:center;">Date d'envoi</th>
            </tr>
          </thead>
          <tbody>
            @foreach($equipement->module4Notifications as $notif)
            <tr>
              <td>
                <strong>{{ $notif->titre ?? 'Notification' }}</strong>
                @if($notif->message)
                  <small class="d-block text-muted">{{ Str::limit($notif->message, 60) }}</small>
                @endif
              </td>
              <td style="text-align:center;">
                <span class="badge bg-light text-dark border">{{ ucfirst($notif->canal ?? 'Push') }}</span>
              </td>
              <td style="text-align:center;">
                {{ $notif->created_at?->format('d/m/Y H:i') ?? '—' }}
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>
    @endif
  </div>

  {{-- Colonne latérale --}}
  <div class="col-lg-4">
    {{-- Synthèse rapide --}}
    <div class="m-card mb-3 p-3" style="border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;">
      <h6 class="fw-bold text-dark mb-3">
        <i class="fa-solid fa-clipboard-check me-1 text-primary"></i>Statut de vigilance
      </h6>
      <div class="p-3 rounded-3 mb-3 text-center" style="background:#ffffff;border:1px solid #e2e8f0;">
        <i class="{{ $equipement->type_icon }} fa-2x mb-2 d-block" style="color:{{ $equipement->niveau_sensibilite === 'eleve' ? '#dc2626' : '#2563eb' }};"></i>
        <h6 class="fw-bold mb-1">{{ $equipement->nom }}</h6>
        <span class="badge {{ $badgeClass }} mb-2">{{ $equipement->niveau_label }}</span>
        <div class="small text-muted">
          {{ $equipement->actif ? 'Surveillance continue active' : 'Surveillance temporairement coupée' }}
        </div>
      </div>
      <div class="small text-muted">
        <div><strong>Dernière modification :</strong> {{ $equipement->updated_at?->format('d/m/Y H:i') ?? '—' }}</div>
        <div class="mt-1"><strong>Identifiant système :</strong> #{{ $equipement->id }}</div>
      </div>
    </div>

    {{-- Actions rapides --}}
    <div class="m-card p-3" style="border-radius:12px;border:1px solid #e2e8f0;">
      <h6 class="fw-bold text-dark mb-3">
        <i class="fa-solid fa-bolt me-1 text-warning"></i>Actions rapides
      </h6>
      <div class="d-grid gap-2">
        <a href="{{ route('admin.equipements-sensibles.edit', $equipement) }}" class="m-btn m-btn--primary justify-content-center">
          <i class="fa-solid fa-pen-to-square me-1"></i>Modifier les informations
        </a>
        <a href="{{ route('admin.equipements-sensibles.index') }}" class="m-btn m-btn--ghost justify-content-center">
          <i class="fa-solid fa-list me-1"></i>Retour au répertoire
        </a>
      </div>
    </div>
  </div>
</div>
@endsection
