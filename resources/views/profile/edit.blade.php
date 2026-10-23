@extends('layouts.front.front')

@section('content')
{{-- ═══════════════════════════════════════════════════════
     PAGE MON PROFIL — HEATALERT TUNISIE
     Design Premium & UX/UI Friendly
═══════════════════════════════════════════════════════ --}}

<!-- En-tête Page Title avec Gradient & Breadcrumbs -->
<div class="page-title position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 58, 95, 0.88) 100%), url('{{ asset('assets/front/img/health/facilities-9.webp') }}') center/cover no-repeat; padding-top: 130px; padding-bottom: 45px; border-bottom: 1px solid rgba(255,255,255,0.1);">
  <div class="heading">
    <div class="container">
      <div class="row justify-content-center text-center">
        <div class="col-lg-8">
          <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-3 rounded-pill bg-white bg-opacity-10 text-white small fw-bold backdrop-blur border border-white border-opacity-20 shadow-sm">
            <span class="d-inline-block rounded-circle bg-success" style="width: 8px; height: 8px; box-shadow: 0 0 10px #22c55e;"></span>
            <span>Espace Citoyen & Prévention Thermique</span>
          </div>
          <h1 class="heading-title text-white" style="font-weight: 800; font-size: 34px; letter-spacing: -0.5px;">
            Mon Espace Personnel
          </h1>
          <p class="mb-0" style="font-size: 15px; color: #f1f5f9; max-width: 620px; margin: 0 auto; line-height: 1.6;">
            Gérez vos informations de compte, votre zone géographique de surveillance prioritaire et vos paramètres de sécurité.
          </p>
        </div>
      </div>
    </div>
  </div>
  <nav class="breadcrumbs mt-4" style="background: rgba(0, 0, 0, 0.35); backdrop-filter: blur(8px); border-top: 1px solid rgba(255,255,255,0.1);">
    <div class="container">
      <ol class="mb-0">
        <li><a href="{{ route('front.home') }}" class="text-white-50">Accueil</a></li>
        <li class="current text-white fw-semibold">Mon Profil</li>
      </ol>
    </div>
  </nav>
</div>

<!-- Contenu Principal -->
<section class="py-5" style="background-color: #f8fafc; min-height: 70vh;">
  <div class="container">

    {{-- Messages Flash de succès --}}
    @if(session('status') === 'profile-updated')
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 p-3 mb-4" role="alert" style="background: #ecfdf5; border-left: 5px solid #10b981 !important;">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: #10b981; color: white;">
          <i class="bi bi-check-lg fs-5"></i>
        </div>
        <div class="flex-grow-1">
          <h6 class="fw-bold mb-0 text-success">Profil mis à jour avec succès !</h6>
          <small class="text-muted">Vos modifications personnelles ont été enregistrées en temps réel.</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
    @endif

    @if(session('status') === 'password-updated')
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 p-3 mb-4" role="alert" style="background: #ecfdf5; border-left: 5px solid #10b981 !important;">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: #10b981; color: white;">
          <i class="bi bi-shield-check fs-5"></i>
        </div>
        <div class="flex-grow-1">
          <h6 class="fw-bold mb-0 text-success">Mot de passe actualisé !</h6>
          <small class="text-muted">Votre mot de passe a été modifié avec succès. Votre compte est sécurisé.</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
    @endif

    @if(session('status') === '2fa-enabled')
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 p-3 mb-4" role="alert" style="background: #ecfdf5; border-left: 5px solid #10b981 !important;">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: #10b981; color: white;">
          <i class="bi bi-shield-lock-fill fs-5"></i>
        </div>
        <div class="flex-grow-1">
          <h6 class="fw-bold mb-0 text-success">Authentification à deux facteurs activée !</h6>
          <small class="text-muted">Votre compte est maintenant protégé par la 2FA. Conservez vos codes de sauvegarde.</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
    @endif

    @if(session('status') === '2fa-disabled')
      <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 p-3 mb-4" role="alert" style="background: #fffbeb; border-left: 5px solid #f59e0b !important;">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px; background: #f59e0b; color: white;">
          <i class="bi bi-shield-exclamation fs-5"></i>
        </div>
        <div class="flex-grow-1">
          <h6 class="fw-bold mb-0" style="color:#92400e;">Authentification à deux facteurs désactivée</h6>
          <small class="text-muted">La 2FA a été retirée de votre compte. Vous pouvez la réactiver à tout moment.</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
    @endif

    {{-- ── 1. BANNIÈRE PROFIL / USER SPOTLIGHT CARD ── --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
      <div class="p-4 p-md-5" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); position: relative;">
        {{-- Décoration d'arrière-plan --}}
        <div class="position-absolute end-0 top-0 opacity-10 pe-4 pt-3 d-none d-md-block" style="font-size: 8rem; pointer-events: none; color: #ffffff;">
<i class="bi bi-person-badge" style="opacity: 0.2;"></i>
        </div>

        <div class="row align-items-center g-4 position-relative">
          {{-- Avatar & Identité --}}
          <div class="col-lg-8">
            <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start text-center text-sm-start gap-4">
              {{-- Avatar monogramme avec badge --}}
              <div class="position-relative flex-shrink-0">
                @php
                  $initials = strtoupper(substr($user->prenom ?? $user->nom, 0, 1) . substr($user->nom, 0, 1));
                @endphp
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-lg"
                     style="width: 90px; height: 90px; font-size: 32px; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border: 3px solid rgba(255,255,255,0.25);">
                  {{ $initials }}
                </div>
                <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white border-2 rounded-circle"
                      title="Compte Actif" style="width: 18px; height: 18px;">
                  <span class="visually-hidden">En ligne</span>
                </span>
              </div>

              {{-- Infos texte --}}
              <div class="text-white">
                <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 mb-1">
                  <h2 class="h3 fw-bold mb-0 text-white">{{ $user->prenom }} {{ $user->nom }}</h2>
                  @if($user->isAdmin())
                    <span class="badge bg-danger rounded-pill px-3 py-1 fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                      <i class="bi bi-shield-lock-fill me-1"></i>Admin
                    </span>
                  @else
                    <span class="badge bg-primary bg-opacity-25 text-white border border-primary border-opacity-50 rounded-pill px-3 py-1 fw-semibold" style="font-size: 11px;">
                      <i class="bi bi-person-check-fill me-1"></i>Citoyen
                    </span>
                  @endif
                </div>

                <div class="text-white-50 small mb-3">
                  <i class="bi bi-envelope me-1"></i>{{ $user->email }}
                  @if($user->telephone)
                    <span class="mx-2">•</span>
                    <i class="bi bi-telephone me-1"></i>{{ $user->telephone }}
                  @endif
                </div>

                {{-- Badges rapides --}}
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-sm-start">
                  @if($user->zone)
                    <span class="badge bg-white bg-opacity-10 text-white border border-white border-opacity-20 px-3 py-2 rounded-pill fw-normal">
                      <i class="bi bi-geo-alt-fill text-warning me-1"></i>{{ $user->zone->nom }} ({{ $user->zone->gouvernorat }})
                    </span>
                  @else
                    <span class="badge bg-white bg-opacity-10 text-white-50 border border-white border-opacity-10 px-3 py-2 rounded-pill fw-normal">
                      <i class="bi bi-geo me-1"></i>Aucune zone rattachée
                    </span>
                  @endif

                  <span class="badge bg-white bg-opacity-10 text-white-50 border border-white border-opacity-10 px-3 py-2 rounded-pill fw-normal">
                    <i class="bi bi-calendar3 me-1"></i>Membre depuis {{ $user->created_at ? $user->created_at->translatedFormat('F Y') : '2026' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          {{-- Actions rapides --}}
          <div class="col-lg-4 text-center text-lg-end">
            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-lg-end">
              <button type="button" class="btn btn-primary px-3 py-2 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-2" onclick="switchProfileTab('edit-tab')">
                <i class="bi bi-pencil-square"></i>
                <span>Modifier le profil</span>
              </button>
              <button type="button" class="btn btn-outline-light px-3 py-2 rounded-pill fw-semibold d-inline-flex align-items-center gap-2" onclick="switchProfileTab('security-tab')">
                <i class="bi bi-key-fill"></i>
                <span>Sécurité</span>
              </button>
              <a href="{{ route('front.equipements_sensibles.index') }}" class="btn btn-danger px-3 py-2 rounded-pill fw-semibold d-inline-flex align-items-center gap-2">
                <i class="bi bi-shield-heart"></i><span>Ma préparation</span>
              </a>
            </div>
          </div>
        </div>
      </div>

      {{-- Barre d'onglets de navigation moderne --}}
      <div class="border-top px-4 bg-light">
        <ul class="nav nav-tabs border-0 flex-nowrap overflow-x-auto" id="profileTab" role="tablist" style="scrollbar-width: none;">
          <li class="nav-item" role="presentation">
            <button class="nav-link active py-3 px-4 fw-semibold border-0 text-muted position-relative" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
              <i class="bi bi-person-lines-fill me-2 text-primary"></i>Vue d'ensemble & Détails
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-3 px-4 fw-semibold border-0 text-muted position-relative" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab">
              <i class="bi bi-pencil-square me-2 text-primary"></i>Modifier mes Informations
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-3 px-4 fw-semibold border-0 text-muted position-relative" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
              <i class="bi bi-shield-lock me-2 text-primary"></i>Mot de Passe & Sécurité
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-3 px-4 fw-semibold border-0 text-muted position-relative text-danger" id="danger-tab" data-bs-toggle="tab" data-bs-target="#danger" type="button" role="tab">
              <i class="bi bi-trash3 me-2 text-danger"></i>Zone Danger
            </button>
          </li>
        </ul>
      </div>
    </div>

    {{-- ── 2. CONTENU DES ONGLETS ── --}}
    <div class="tab-content" id="profileTabContent">

      {{-- ══════════════════════════════════════════════════════
           ONGLET 1 : VUE D'ENSEMBLE & DÉTAILS
      ══════════════════════════════════════════════════════ --}}
      <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
        <div class="row g-4">

          {{-- Colonne Gauche : Carte d'identité complète --}}
          <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white p-4">
              <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-person-vcard fs-4"></i>
                  </div>
                  <div>
                    <h5 class="fw-bold mb-0 text-dark">Informations Personnelles</h5>
                    <small class="text-muted">Détails de votre compte citoyen</small>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="switchProfileTab('edit-tab')">
                  <i class="bi bi-pencil me-1"></i>Modifier
                </button>
              </div>

              <div class="row g-3">
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 bg-light border border-light">
                    <span class="text-muted small d-block mb-1">Prénom</span>
                    <strong class="text-dark fs-6">{{ $user->prenom ?? 'Non renseigné' }}</strong>
                  </div>
                </div>

                <div class="col-sm-6">
                  <div class="p-3 rounded-3 bg-light border border-light">
                    <span class="text-muted small d-block mb-1">Nom de famille</span>
                    <strong class="text-dark fs-6">{{ $user->nom ?? 'Non renseigné' }}</strong>
                  </div>
                </div>

                <div class="col-sm-6">
                  <div class="p-3 rounded-3 bg-light border border-light">
                    <span class="text-muted small d-block mb-1">Adresse Email</span>
                    <strong class="text-dark fs-6 text-truncate d-block" title="{{ $user->email }}">{{ $user->email }}</strong>
                    <div class="mt-1">
                      <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 11px;">
                        <i class="bi bi-check-circle-fill me-1"></i>Compte Vérifié
                      </span>
                    </div>
                  </div>
                </div>

                <div class="col-sm-6">
                  <div class="p-3 rounded-3 bg-light border border-light">
                    <span class="text-muted small d-block mb-1">Numéro de Téléphone</span>
                    @if($user->telephone)
                      <strong class="text-dark fs-6">{{ $user->telephone }}</strong>
                      <div class="mt-1 text-muted small"><i class="bi bi-chat-text text-primary me-1"></i>Alertes SMS activables</div>
                    @else
                      <span class="text-muted fst-italic">Non renseigné</span>
                      <div class="mt-1"><a href="javascript:void(0)" onclick="switchProfileTab('edit-tab')" class="text-primary small text-decoration-none">+ Ajouter un numéro</a></div>
                    @endif
                  </div>
                </div>

                <div class="col-sm-6">
                  <div class="p-3 rounded-3 bg-light border border-light">
                    <span class="text-muted small d-block mb-1">Rôle & Permissions</span>
                    <div class="d-flex align-items-center gap-2">
                      <strong class="text-dark fs-6">{{ $user->isAdmin() ? 'Administrateur Général' : 'Utilisateur Citoyen' }}</strong>
                    </div>
                    <small class="text-muted d-block mt-1">Accès aux fonctionnalités {{ $user->isAdmin() ? 'complètes (back-office & statistiques)' : 'de consultation et vigilance' }}</small>
                  </div>
                </div>

                <div class="col-sm-6">
                  <div class="p-3 rounded-3 bg-light border border-light">
                    <span class="text-muted small d-block mb-1">Date d'inscription</span>
                    <strong class="text-dark fs-6">
                      {{ $user->created_at ? $user->created_at->format('d/m/Y à H:i') : 'Récemment' }}
                    </strong>
                    <small class="text-muted d-block mt-1">
                      {{ $user->created_at ? $user->created_at->diffForHumans() : '' }}
                    </small>
                  </div>
                </div>
              </div>

              {{-- Conseils rapides de protection --}}
              <div class="mt-4 p-3 rounded-3 border d-flex align-items-center gap-3" style="background: #eff6ff; border-color: #bfdbfe !important;">
                <div class="text-primary fs-3 flex-shrink-0">
                  <i class="bi bi-info-circle-fill"></i>
                </div>
                <div class="small text-muted">
                  <strong class="text-dark d-block">Astuce HeatAlert :</strong>
                  Renseignez votre zone de vie pour être immédiatement averti par notification lorsque votre secteur entre en vigilance canicule orange ou rouge.
                </div>
              </div>

            </div>
          </div>

          {{-- Colonne Droite : Ma Zone de Surveillance & Alertes --}}
          <div class="col-lg-5">
            <div class="d-flex flex-column gap-4">

              {{-- Carte Zone favorite --}}
              <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                  <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-2 bg-warning bg-opacity-10 text-warning">
                      <i class="bi bi-geo-alt-fill fs-4"></i>
                    </div>
                    <div>
                      <h5 class="fw-bold mb-0 text-dark">Ma Zone Surveillée</h5>
                      <small class="text-muted">Votre localisation prioritaire</small>
                    </div>
                  </div>
                  @if($user->zone)
                    <a href="{{ route('front.zones.show', $user->zone) }}" class="btn btn-sm btn-primary rounded-pill px-3">
                      <i class="bi bi-arrow-right me-1"></i>Météo
                    </a>
                  @endif
                </div>

                @if($user->zone)
                  <div class="p-3 rounded-4 mb-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0;">
                    <div class="d-flex align-items-start justify-content-between">
                      <div>
                        <span class="badge bg-success rounded-pill px-2.5 py-1 mb-2" style="font-size: 11px;">Zone Rattachée</span>
                        <h4 class="fw-bold text-dark mb-1">{{ $user->zone->nom }}</h4>
                        <div class="text-muted small">
                          <i class="bi bi-pin-map-fill text-danger me-1"></i>{{ $user->zone->ville }} — Gouvernorat de <strong>{{ $user->zone->gouvernorat }}</strong>
                        </div>
                      </div>
                      <div class="rounded-circle bg-white p-2.5 shadow-sm text-success">
                        <i class="bi bi-check-circle-fill fs-3"></i>
                      </div>
                    </div>
                    @if($user->zone->code_postal)
                      <div class="mt-2 text-muted small">
                        Code Postal : <span class="fw-semibold text-dark">{{ $user->zone->code_postal }}</span>
                      </div>
                    @endif
                  </div>

                  {{-- Alertes actives dans cette zone --}}
                  <h6 class="fw-bold text-dark small text-uppercase mb-2" style="letter-spacing: 0.5px;">
                    <i class="bi bi-bell-fill text-danger me-1"></i>Vigilance Canicule Active
                  </h6>

                  @if($alertesZone->count() > 0)
                    <div class="d-flex flex-column gap-2">
                      @foreach($alertesZone as $alerte)
                        @php
                          $color = match($alerte->niveau) {
                            'rouge' => '#dc2626',
                            'orange' => '#ea580c',
                            'jaune' => '#eab308',
                            default => '#22c55e',
                          };
                        @endphp
                        <a href="{{ route('front.alertes.show', $alerte) }}" class="p-3 rounded-3 border text-decoration-none d-block transition-all" style="background: {{ $color }}08; border-color: {{ $color }}40 !important;">
                          <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="badge text-white fw-bold text-uppercase" style="background: {{ $color }}; font-size: 10px;">
                              {{ $alerte->niveau }}
                            </span>
                            @if($alerte->temperature_max)
                              <span class="fw-bold text-danger small">
                                <i class="bi bi-thermometer-high"></i>{{ $alerte->temperature_max }}°C
                              </span>
                            @endif
                          </div>
                          <div class="fw-bold text-dark small text-truncate">{{ $alerte->titre }}</div>
                          <div class="text-muted" style="font-size: 11px;">
                            Début : {{ $alerte->date_debut ? $alerte->date_debut->format('d/m à H:i') : 'En cours' }}
                          </div>
                        </a>
                      @endforeach
                    </div>
                  @else
                    <div class="p-3 rounded-3 text-center border" style="background: #f8fafc;">
                      <i class="bi bi-shield-check text-success fs-2 mb-1 d-block"></i>
                      <div class="fw-bold text-dark small">Aucune alerte critique en cours</div>
                      <small class="text-muted">Les conditions thermiques sont normales dans votre zone.</small>
                    </div>
                  @endif

                @else
                  {{-- Aucune zone configurée --}}
                  <div class="p-4 rounded-4 text-center border border-dashed" style="background: #fafaf9;">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3 text-muted" style="width: 50px; height: 50px; background: #e2e8f0;">
                      <i class="bi bi-geo fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark">Aucune zone favorite définie</h6>
                    <p class="text-muted small mb-3">
                      En associant votre ville de résidence, vous recevrez en priorité le bulletin météo et les alertes canicule qui vous concernent directement.
                    </p>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-4" onclick="switchProfileTab('edit-tab')">
                      <i class="bi bi-plus-circle me-1"></i>Choisir ma zone maintenant
                    </button>
                  </div>
                @endif
              </div>

              {{-- Carte Sécurité & Sessions --}}
              <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                  <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success">
                    <i class="bi bi-shield-lock-fill fs-4"></i>
                  </div>
                  <div>
                    <h6 class="fw-bold mb-0 text-dark">Protection du Compte</h6>
                    <small class="text-muted">État de sécurité et confidentialité</small>
                  </div>
                </div>

                <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                  <li class="d-flex align-items-center justify-content-between p-2 rounded bg-light">
                    <span class="text-muted"><i class="bi bi-key me-2 text-primary"></i>Mot de passe</span>
                    <span class="badge bg-success-subtle text-success">Actif & Chiffré</span>
                  </li>
                  <li class="d-flex align-items-center justify-content-between p-2 rounded bg-light">
                    <span class="text-muted"><i class="bi bi-envelope-check me-2 text-primary"></i>Authentification</span>
                    <span class="badge bg-success-subtle text-success">Email Valide</span>
                  </li>
                </ul>

                <button type="button" class="btn btn-light border rounded-pill w-100 mt-3 small fw-semibold" onclick="switchProfileTab('security-tab')">
                  <i class="bi bi-shield-shaded me-1"></i>Paramètres de sécurité
                </button>
              </div>

            </div>
          </div>

        </div>
      </div>

      {{-- ══════════════════════════════════════════════════════
           ONGLET 2 : MODIFIER MES INFORMATIONS
      ══════════════════════════════════════════════════════ --}}
      <div class="tab-pane fade" id="edit" role="tabpanel" aria-labelledby="edit-tab">
        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 p-md-5">

              <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-person-gear fs-4"></i>
                  </div>
                  <div>
                    <h4 class="fw-bold mb-0 text-dark">Modifier mon Profil</h4>
                    <p class="text-muted mb-0 small">Mettez à jour vos données d'identification et votre localisation</p>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="switchProfileTab('overview-tab')">
                  <i class="bi bi-arrow-left me-1"></i>Retour
                </button>
              </div>

              <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('patch')

                {{-- Messages d'erreurs éventuels --}}
                @if ($errors->any())
                  <div class="alert alert-danger rounded-4 border-0 p-3 mb-4" role="alert">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Veuillez corriger les erreurs suivantes :</div>
                    <ul class="mb-0 small ps-3">
                      @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                      @endforeach
                    </ul>
                  </div>
                @endif

                <div class="row g-4">
                  {{-- Prénom --}}
                  <div class="col-md-6">
                    <label for="prenom" class="form-label fw-bold small text-dark">
                      Prénom <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-person"></i>
                      </span>
                      <input type="text"
                             class="form-control bg-light border-start-0 @error('prenom') is-invalid @enderror"
                             id="prenom"
                             name="prenom"
                             value="{{ old('prenom', $user->prenom) }}"
                             required
                             autocomplete="given-name"
                             placeholder="Ex: Mohamed">
                    </div>
                    @error('prenom')
                      <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                  </div>

                  {{-- Nom --}}
                  <div class="col-md-6">
                    <label for="nom" class="form-label fw-bold small text-dark">
                      Nom de Famille <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-person-fill"></i>
                      </span>
                      <input type="text"
                             class="form-control bg-light border-start-0 @error('nom') is-invalid @enderror"
                             id="nom"
                             name="nom"
                             value="{{ old('nom', $user->nom) }}"
                             required
                             autocomplete="family-name"
                             placeholder="Ex: Ben Salem">
                    </div>
                    @error('nom')
                      <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                  </div>

                  {{-- Email --}}
                  <div class="col-md-6">
                    <label for="email" class="form-label fw-bold small text-dark">
                      Adresse Email <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-envelope"></i>
                      </span>
                      <input type="email"
                             class="form-control bg-light border-start-0 @error('email') is-invalid @enderror"
                             id="email"
                             name="email"
                             value="{{ old('email', $user->email) }}"
                             required
                             autocomplete="email"
                             placeholder="nom@exemple.com">
                    </div>
                    <small class="text-muted" style="font-size: 11.5px;">Utilisée pour vos connexions et vos alertes météo prioritaires.</small>
                    @error('email')
                      <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                  </div>

                  {{-- Téléphone --}}
                  <div class="col-md-6">
                    <label for="telephone" class="form-label fw-bold small text-dark">
                      Numéro de Téléphone
                    </label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-telephone"></i>
                      </span>
                      <input type="tel"
                             class="form-control bg-light border-start-0 @error('telephone') is-invalid @enderror"
                             id="telephone"
                             name="telephone"
                             value="{{ old('telephone', $user->telephone) }}"
                             placeholder="Ex: +216 98 123 456">
                    </div>
                    <small class="text-muted" style="font-size: 11.5px;">Optionnel — permet la réception d'alertes SMS d'urgence.</small>
                    @error('telephone')
                      <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                  </div>

                  {{-- Zone de Résidence --}}
                  <div class="col-12">
                    <label for="zone_id" class="form-label fw-bold small text-dark">
                      Zone de Surveillance Prioritaire (Résidence / Travail)
                    </label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-geo-alt-fill text-danger"></i>
                      </span>
                      <select class="form-select bg-light border-start-0 @error('zone_id') is-invalid @enderror" id="zone_id" name="zone_id">
                        <option value="">-- Aucune zone rattachée (Surveillance nationale) --</option>
                        @php
                          $zonesParGouv = $zones->groupBy('gouvernorat');
                        @endphp
                        @foreach($zonesParGouv as $gouv => $listeZones)
                          <optgroup label="Gouvernorat : {{ $gouv }}">
                            @foreach($listeZones as $z)
                              <option value="{{ $z->id }}" {{ (string) old('zone_id', $user->zone_id) === (string) $z->id ? 'selected' : '' }}>
                                {{ $z->nom }} ({{ $z->ville }}) @if($z->code_postal) - {{ $z->code_postal }} @endif
                              </option>
                            @endforeach
                          </optgroup>
                        @endforeach
                      </select>
                    </div>
                    <small class="text-muted" style="font-size: 11.5px;">Permet d'afficher la vigilance canicule de votre commune sur votre page d'accueil.</small>
                    @error('zone_id')
                      <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                  </div>
                </div>

                {{-- Boutons d'action --}}
                <div class="d-flex align-items-center justify-content-end gap-3 mt-5 pt-3 border-top">
                  <button type="button" class="btn btn-light rounded-pill px-4" onclick="switchProfileTab('overview-tab')">
                    Annuler
                  </button>
                  <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-check2-circle fs-5"></i>
                    <span>Enregistrer les modifications</span>
                  </button>
                </div>
              </form>

            </div>
          </div>
        </div>
      </div>

      {{-- ══════════════════════════════════════════════════════
           ONGLET 3 : MOT DE PASSE & SÉCURITÉ
      ══════════════════════════════════════════════════════ --}}
      <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 p-md-5">

              <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success">
                    <i class="bi bi-shield-lock fs-4"></i>
                  </div>
                  <div>
                    <h4 class="fw-bold mb-0 text-dark">Sécurité & Mot de Passe</h4>
                    <p class="text-muted mb-0 small">Modifiez votre mot de passe pour garantir la confidentialité de vos accès</p>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="switchProfileTab('overview-tab')">
                  <i class="bi bi-arrow-left me-1"></i>Retour
                </button>
              </div>

              <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('put')

                {{-- Erreurs spécifiques au mot de passe --}}
                @if ($errors->updatePassword->any())
                  <div class="alert alert-danger rounded-4 border-0 p-3 mb-4" role="alert">
                    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Erreur lors de la mise à jour du mot de passe :</div>
                    <ul class="mb-0 small ps-3">
                      @foreach ($errors->updatePassword->all() as $error)
                        <li>{{ $error }}</li>
                      @endforeach
                    </ul>
                  </div>
                @endif

                {{-- Mot de passe actuel --}}
                <div class="mb-4">
                  <label for="current_password" class="form-label fw-bold small text-dark">
                    Mot de passe actuel <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                      <i class="bi bi-lock"></i>
                    </span>
                    <input type="password"
                           class="form-control bg-light border-start-0 border-end-0 @error('current_password', 'updatePassword') is-invalid @enderror"
                           id="current_password"
                           name="current_password"
                           required
                           autocomplete="current-password"
                           placeholder="Entrez votre mot de passe actuel">
                    <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePasswordVisibility('current_password', this)">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                  @error('current_password', 'updatePassword')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                  @enderror
                </div>

                {{-- Nouveau mot de passe --}}
                <div class="mb-4">
                  <label for="password" class="form-label fw-bold small text-dark">
                    Nouveau mot de passe <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                      <i class="bi bi-key"></i>
                    </span>
                    <input type="password"
                           class="form-control bg-light border-start-0 border-end-0 @error('password', 'updatePassword') is-invalid @enderror"
                           id="password"
                           name="password"
                           required
                           autocomplete="new-password"
                           placeholder="Minimum 8 caractères"
                           oninput="checkPasswordStrength(this.value)">
                    <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePasswordVisibility('password', this)">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                  @error('password', 'updatePassword')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                  @enderror

                  {{-- Jauge visuelle de robustesse --}}
                  <div class="mt-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="small text-muted" style="font-size: 11px;">Force du mot de passe :</span>
                      <span class="small fw-bold" id="pwd-strength-text" style="font-size: 11px; color: #94a3b8;">Non renseigné</span>
                    </div>
                    <div class="progress" style="height: 5px;">
                      <div class="progress-bar transition-all" id="pwd-strength-bar" role="progressbar" style="width: 0%; background-color: #cbd5e1;"></div>
                    </div>
                  </div>
                </div>

                {{-- Confirmation nouveau mot de passe --}}
                <div class="mb-4">
                  <label for="password_confirmation" class="form-label fw-bold small text-dark">
                    Confirmer le nouveau mot de passe <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                      <i class="bi bi-shield-check"></i>
                    </span>
                    <input type="password"
                           class="form-control bg-light border-start-0 border-end-0 @error('password_confirmation', 'updatePassword') is-invalid @enderror"
                           id="password_confirmation"
                           name="password_confirmation"
                           required
                           autocomplete="new-password"
                           placeholder="Répétez fidèlement votre mot de passe">
                    <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePasswordVisibility('password_confirmation', this)">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                  @error('password_confirmation', 'updatePassword')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                  @enderror
                </div>

                <div class="p-3 rounded-3 bg-light border mb-4 small text-muted">
                  <div class="fw-bold text-dark mb-1"><i class="bi bi-shield-shaded text-success me-1"></i>Critères de sécurité recommandés :</div>
                  <ul class="mb-0 ps-3">
                    <li>Au moins 8 caractères</li>
                    <li>Mélange de lettres minuscules, majuscules et chiffres</li>
                    <li>Un caractère spécial (!, @, #, $, etc.)</li>
                  </ul>
                </div>

                {{-- Bouton submit --}}
                <div class="d-flex align-items-center justify-content-end gap-3 pt-3 border-top">
                  <button type="submit" class="btn btn-success rounded-pill px-5 py-2.5 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock-fill fs-5"></i>
                    <span>Mettre à jour le mot de passe</span>
                  </button>
                </div>
              </form>

              {{-- ═══════════════════════════════════
                   SECTION 2FA — TOTP (QR CODE)
              ═══════════════════════════════════ --}}
              <div class="mt-5 pt-4 border-top">
                <div class="d-flex align-items-center gap-3 mb-4">
                  <div class="rounded-3 p-2 bg-dark bg-opacity-10 text-dark">
                    <i class="bi bi-qr-code fs-4"></i>
                  </div>
                  <div>
                    <h5 class="fw-bold mb-0 text-dark">Authentification à deux facteurs (2FA)</h5>
                    <small class="text-muted">Renforcez la sécurité de votre compte avec une application TOTP</small>
                  </div>
                  @if($user->two_factor_enabled)
                    <span class="ms-auto badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold">
                      <i class="bi bi-shield-fill-check me-1"></i>Activée
                    </span>
                  @else
                    <span class="ms-auto badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill fw-semibold">
                      <i class="bi bi-shield me-1"></i>Désactivée
                    </span>
                  @endif
                </div>

                @if($user->two_factor_enabled)
                  {{-- 2FA ACTIVE : afficher options de désactivation --}}
                  <div class="p-4 rounded-4 mb-4" style="background: linear-gradient(135deg,#ecfdf5,#d1fae5); border: 1px solid #6ee7b7;">
                    <div class="d-flex align-items-start gap-3">
                      <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;background:#10b981;color:#fff;font-size:1.4rem;">🛡️</div>
                      <div>
                        <h6 class="fw-bold text-dark mb-1">Votre compte est protégé par la 2FA</h6>
                        <p class="text-muted small mb-0">
                          À chaque connexion, vous devrez saisir un code à 6 chiffres généré par votre application d'authentification (Google Authenticator, Authy, etc.).
                        </p>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex flex-wrap gap-3 align-items-center p-3 rounded-3 bg-light border">
                    <div class="d-flex align-items-center gap-2 small text-muted">
                      <i class="bi bi-phone text-primary fs-5"></i>
                      <span>Lié à votre application TOTP</span>
                    </div>
                    <button type="button" class="ms-auto btn btn-outline-danger rounded-pill px-4 fw-semibold"
                            data-bs-toggle="modal" data-bs-target="#disable2FAModal">
                      <i class="bi bi-shield-x me-1"></i>Désactiver la 2FA
                    </button>
                  </div>

                @else
                  {{-- 2FA INACTIVE : bouton pour afficher le QR Code --}}
                  <div class="p-4 rounded-4 mb-4" style="background: linear-gradient(135deg,#eff6ff,#dbeafe); border: 1px solid #93c5fd;">
                    <div class="d-flex align-items-start gap-3">
                      <div style="font-size:2rem;flex-shrink:0;">📱</div>
                      <div>
                        <h6 class="fw-bold text-dark mb-1">Activez la 2FA pour sécuriser votre compte</h6>
                        <p class="text-muted small mb-2">
                          Scannez un QR Code avec <strong>Google Authenticator</strong>, <strong>Authy</strong> ou toute autre application TOTP. Aucun numéro de téléphone requis.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                          <span class="badge bg-white border text-dark small"><i class="bi bi-check-circle-fill text-success me-1"></i>100% gratuit</span>
                          <span class="badge bg-white border text-dark small"><i class="bi bi-check-circle-fill text-success me-1"></i>Fonctionne hors-ligne</span>
                          <span class="badge bg-white border text-dark small"><i class="bi bi-check-circle-fill text-success me-1"></i>Code valide 30 secondes</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <button type="button" class="btn btn-dark rounded-pill px-5 py-2 fw-semibold d-inline-flex align-items-center gap-2"
                          id="btn-setup-2fa">
                    <i class="bi bi-qr-code"></i> Configurer la 2FA avec QR Code
                  </button>

                  {{-- Zone QR Code (cachée par défaut) --}}
                  <div id="qr-setup-zone" style="display:none;" class="mt-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4" style="background:#f8fafc;">

                      <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-3 p-1 bg-dark text-white" style="font-size:.9rem;padding:6px 10px!important;">Étape 1</div>
                        <span class="fw-bold text-dark small">Scannez ce QR Code avec votre application</span>
                      </div>

                      {{-- QR Code SVG généré dynamiquement --}}
                      <div class="text-center mb-3">
                        <div id="qr-loading" class="py-4">
                          <div class="spinner-border text-dark" role="status"></div>
                          <p class="text-muted small mt-2">Génération du QR Code…</p>
                        </div>
                        <div id="qr-svg-container" style="display:none;">
                          <div class="d-inline-block p-3 bg-white rounded-3 shadow-sm border" id="qr-svg-wrap"></div>
                          <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="btn-show-secret">
                              <i class="bi bi-key me-1"></i>Afficher la clé manuelle
                            </button>
                          </div>
                          <div id="secret-zone" style="display:none;" class="mt-2">
                            <div class="p-2 rounded bg-light border d-inline-block">
                              <code id="secret-text" style="font-size:.85rem;letter-spacing:2px;"></code>
                            </div>
                            <p class="text-muted" style="font-size:11px;">Saisie manuelle si le scan ne fonctionne pas.</p>
                          </div>
                        </div>
                      </div>

                      <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-3 p-1 bg-dark text-white" style="font-size:.9rem;padding:6px 10px!important;">Étape 2</div>
                        <span class="fw-bold text-dark small">Confirmez avec le code affiché dans l'application</span>
                      </div>

                      <form method="POST" action="{{ route('2fa.enable') }}" id="enable-2fa-form">
                        @csrf
                        <div class="mb-3">
                          <div class="d-flex gap-2 justify-content-center" id="totp-digits">
                            @for($d = 0; $d < 6; $d++)
                              <input type="text"
                                     class="form-control text-center fw-bold fs-4 totp-digit-input"
                                     maxlength="1" inputmode="numeric" pattern="[0-9]"
                                     id="td-{{ $d }}"
                                     autocomplete="off"
                                     style="width:52px;height:60px;border-radius:12px;border:2px solid #e2e8f0;">
                            @endfor
                          </div>
                          <input type="hidden" name="totp_code" id="totp-hidden">
                          @error('totp_code')
                            <div class="text-danger small mt-2 text-center">{{ $message }}</div>
                          @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                          <button type="button" class="btn btn-light border rounded-pill px-4" id="btn-cancel-2fa">
                            Annuler
                          </button>
                          <button type="submit" class="btn btn-dark rounded-pill px-5 fw-semibold d-inline-flex align-items-center gap-2"
                                  id="btn-confirm-2fa" disabled>
                            <i class="bi bi-shield-lock-fill"></i> Activer la 2FA
                          </button>
                        </div>
                      </form>

                    </div>
                  </div>
                @endif
              </div>
              {{-- FIN SECTION 2FA --}}

            </div>
          </div>
        </div>
      </div>

      {{-- ══════════════════════════════════════════════════════
           ONGLET 4 : ZONE DE DANGER (SUPPRESSION)
      ══════════════════════════════════════════════════════ --}}
      <div class="tab-pane fade" id="danger" role="tabpanel" aria-labelledby="danger-tab">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 p-md-5 border-start border-danger border-4">

              <div class="d-flex align-items-center gap-3 pb-3 mb-4 border-bottom">
                <div class="rounded-3 p-2 bg-danger bg-opacity-10 text-danger">
                  <i class="bi bi-exclamation-triangle fs-4"></i>
                </div>
                <div>
                  <h4 class="fw-bold mb-0 text-danger">Suppression Définitive du Compte</h4>
                  <p class="text-muted mb-0 small">Cette action est irréversible et supprimera vos préférences</p>
                </div>
              </div>

              <div class="p-4 rounded-4 mb-4" style="background: #fef2f2; border: 1px solid #fee2e2;">
                <p class="text-dark mb-2 fw-semibold">
                  Êtes-vous sûr de vouloir supprimer votre compte HeatAlert ?
                </p>
                <p class="text-muted small mb-0">
                  Une fois votre compte supprimé, toutes vos données personnelles, associations de zones favorites et préférences de notifications seront définitivement purgées de notre base de données.
                </p>
              </div>

              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-danger rounded-pill px-4 py-2.5 fw-semibold d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#confirmUserDeletionModal">
                  <i class="bi bi-trash3-fill"></i>
                  <span>Supprimer mon compte</span>
                </button>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
</section>

{{-- ── MODAL CONFIRMATION SUPPRESSION COMPTE ── --}}
<div class="modal fade" id="confirmUserDeletionModal" tabindex="-1" aria-labelledby="confirmUserDeletionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <form method="POST" action="{{ route('profile.destroy') }}">
        @csrf
        @method('delete')

        <div class="modal-header border-0 pb-0">
          <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-3 d-inline-flex">
            <i class="bi bi-shield-exclamation fs-3"></i>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>

        <div class="modal-body pt-3 pb-4">
          <h5 class="fw-bold text-dark mb-2" id="confirmUserDeletionModalLabel">Confirmer la suppression ?</h5>
          <p class="text-muted small mb-3">
            Veuillez saisir votre mot de passe pour confirmer que vous êtes bien à l'origine de cette demande de suppression.
          </p>

          <div class="mb-3">
            <label for="delete_password" class="form-label small fw-bold text-dark">Votre mot de passe</label>
            <input type="password"
                   class="form-control @error('password', 'userDeletion') is-invalid @enderror"
                   id="delete_password"
                   name="password"
                   required
                   placeholder="Mot de passe actuel">
            @error('password', 'userDeletion')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="modal-footer border-0 pt-0 bg-light rounded-bottom-4">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger rounded-pill px-4">Supprimer définitivement</button>
        </div>
      </form>
    </div>
</div>

{{-- ── MODAL DÉSACTIVATION 2FA ── --}}
<div class="modal fade" id="disable2FAModal" tabindex="-1" aria-labelledby="disable2FAModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <form method="POST" action="{{ route('2fa.disable') }}">
        @csrf

        <div class="modal-header border-0 pb-0">
          <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3 d-inline-flex">
            <i class="bi bi-shield-x fs-3"></i>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>

        <div class="modal-body pt-3 pb-4">
          <h5 class="fw-bold text-dark mb-2" id="disable2FAModalLabel">Désactiver l'authentification 2FA ?</h5>
          <p class="text-muted small mb-3">
            Votre compte ne sera plus protégé par le second facteur TOTP. Pour confirmer la désactivation, veuillez saisir votre mot de passe actuel.
          </p>

          <div class="mb-3">
            <label for="disable_2fa_password" class="form-label small fw-bold text-dark">Votre mot de passe actuel</label>
            <input type="password"
                   class="form-control @error('password', 'disable2fa') is-invalid @enderror"
                   id="disable_2fa_password"
                   name="password"
                   required
                   placeholder="Mot de passe actuel">
            @error('password', 'disable2fa')
              <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>
        </div>

        <div class="modal-footer border-0 pt-0 bg-light rounded-bottom-4">
          <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger rounded-pill px-4 fw-semibold">
            <i class="bi bi-shield-x me-1"></i> Désactiver la 2FA
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  /* Navigation Tabs Modernes */
  #profileTab .nav-link {
    color: #64748b;
    border-bottom: 3px solid transparent !important;
    transition: all 0.25s ease;
  }
  #profileTab .nav-link:hover {
    color: #0f172a;
    background: rgba(0, 0, 0, 0.02);
  }
  #profileTab .nav-link.active {
    color: #2563eb !important;
    background: transparent;
    border-bottom: 3px solid #2563eb !important;
  }
  #profileTab .nav-link#danger-tab.active {
    color: #dc2626 !important;
    border-bottom-color: #dc2626 !important;
  }

  /* Micro interactions */
  .transition-all {
    transition: all 0.3s ease;
  }
  .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .form-control:focus, .form-select:focus {
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    background-color: #ffffff !important;
  }
</style>
@endpush

@push('scripts')
<script>
/**
 * Changement de Tab Programmatique
 */
function switchProfileTab(tabId) {
  const triggerEl = document.getElementById(tabId);
  if (triggerEl) {
    const tabInstance = bootstrap.Tab.getOrCreateInstance(triggerEl);
    tabInstance.show();
    window.scrollTo({ top: 380, behavior: 'smooth' });
  }
}

/**
 * Afficher/Masquer le mot de passe
 */
function togglePasswordVisibility(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const icon = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    if (icon) {
      icon.classList.remove('bi-eye');
      icon.classList.add('bi-eye-slash');
    }
  } else {
    input.type = 'password';
    if (icon) {
      icon.classList.remove('bi-eye-slash');
      icon.classList.add('bi-eye');
    }
  }
}

/**
 * Calcul de la robustesse du mot de passe en direct
 */
function checkPasswordStrength(password) {
  const bar = document.getElementById('pwd-strength-bar');
  const label = document.getElementById('pwd-strength-text');
  if (!bar || !label) return;

  if (!password) {
    bar.style.width = '0%';
    bar.style.backgroundColor = '#cbd5e1';
    label.textContent = 'Non renseigné';
    label.style.color = '#94a3b8';
    return;
  }

  let score = 0;
  if (password.length >= 8) score++;
  if (password.length >= 12) score++;
  if (/[A-Z]/.test(password)) score++;
  if (/[0-9]/.test(password)) score++;
  if (/[^A-Za-z0-9]/.test(password)) score++;

  if (score <= 2) {
    bar.style.width = '30%';
    bar.style.backgroundColor = '#ef4444';
    label.textContent = 'Faible';
    label.style.color = '#ef4444';
  } else if (score <= 4) {
    bar.style.width = '70%';
    bar.style.backgroundColor = '#f59e0b';
    label.textContent = 'Moyen';
    label.style.color = '#f59e0b';
  } else {
    bar.style.width = '100%';
    bar.style.backgroundColor = '#10b981';
    label.textContent = 'Très Robuste';
    label.style.color = '#10b981';
  }
}

/**
 * Sélection automatique de l'onglet selon erreurs de validation ou hash URL
 */
document.addEventListener('DOMContentLoaded', function() {
  @if ($errors->updatePassword->any() || session('status') === 'password-updated' || session('status') === '2fa-enabled' || session('status') === '2fa-disabled' || session('2fa_error') || $errors->has('totp_code') || session('open_2fa_setup'))
    switchProfileTab('security-tab');
  @elseif ($errors->disable2fa->any())
    switchProfileTab('security-tab');
    const disableModalEl = document.getElementById('disable2FAModal');
    if (disableModalEl) {
      const disableModal = new bootstrap.Modal(disableModalEl);
      disableModal.show();
    }
  @elseif ($errors->userDeletion->any())
    const modalEl = document.getElementById('confirmUserDeletionModal');
    if (modalEl) {
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    }
    switchProfileTab('danger-tab');
  @elseif ($errors->any() || session('status') === 'profile-updated')
    switchProfileTab('edit-tab');
  @else
    const hash = window.location.hash;
    if (hash === '#edit') {
      switchProfileTab('edit-tab');
    } else if (hash === '#security') {
      switchProfileTab('security-tab');
    }
  @endif

  // ── 2FA QR Code Setup ──────────────────────────────────────────
  const btnSetup2fa   = document.getElementById('btn-setup-2fa');
  const qrSetupZone   = document.getElementById('qr-setup-zone');
  const qrLoading     = document.getElementById('qr-loading');
  const qrSvgCont     = document.getElementById('qr-svg-container');
  const qrSvgWrap     = document.getElementById('qr-svg-wrap');
  const btnShowSecret = document.getElementById('btn-show-secret');
  const secretZone    = document.getElementById('secret-zone');
  const secretText    = document.getElementById('secret-text');
  const btnCancel2fa  = document.getElementById('btn-cancel-2fa');
  const totpDigits    = Array.from(document.querySelectorAll('.totp-digit-input'));
  const totpHidden    = document.getElementById('totp-hidden');
  const btnConfirm    = document.getElementById('btn-confirm-2fa');

  if (btnSetup2fa) {
    btnSetup2fa.addEventListener('click', function() {
      qrSetupZone.style.display = 'block';
      btnSetup2fa.style.display = 'none';
      qrLoading.style.display = 'block';
      qrSvgCont.style.display = 'none';

      fetch('{{ route("2fa.setup") }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ?
            document.querySelector('meta[name="csrf-token"]').content :
            '{{ csrf_token() }}' }
      })
      .then(r => r.json())
      .then(data => {
        qrLoading.style.display = 'none';
        qrSvgWrap.innerHTML = data.qr_svg;
        if (secretText) secretText.textContent = data.secret;
        qrSvgCont.style.display = 'block';
        // auto-scroll
        qrSetupZone.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (totpDigits[0]) totpDigits[0].focus();
      })
      .catch(() => {
        qrLoading.innerHTML = '<p class="text-danger small">Erreur de chargement. Veuillez réessayer.</p>';
      });
    });
  }

  if (btnShowSecret) {
    btnShowSecret.addEventListener('click', () => {
      secretZone.style.display = secretZone.style.display === 'none' ? 'block' : 'none';
    });
  }

  if (btnCancel2fa) {
    btnCancel2fa.addEventListener('click', () => {
      qrSetupZone.style.display = 'none';
      if (btnSetup2fa) btnSetup2fa.style.display = 'inline-flex';
      totpDigits.forEach(d => d.value = '');
      if (totpHidden) totpHidden.value = '';
      if (btnConfirm) btnConfirm.disabled = true;
    });
  }

  // OTP digit navigation for TOTP setup form
  function updateTotpHidden() {
    const code = totpDigits.map(d => d.value).join('');
    if (totpHidden) totpHidden.value = code;
    if (btnConfirm) btnConfirm.disabled = code.length !== 6;
  }
  totpDigits.forEach((d, i) => {
    d.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !d.value && i > 0) { totpDigits[i-1].focus(); totpDigits[i-1].select(); }
    });
    d.addEventListener('input', () => {
      d.value = d.value.replace(/\D/g, '').slice(-1);
      updateTotpHidden();
      if (d.value && i < 5) totpDigits[i+1].focus();
    });
    d.addEventListener('paste', e => {
      e.preventDefault();
      const p = (e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
      p.split('').forEach((c,j)=>{ if(totpDigits[j]) totpDigits[j].value=c; });
      updateTotpHidden();
      totpDigits[Math.min(p.length,5)].focus();
    });
  });

  // Si erreur totp_code, ré-afficher la zone de setup
  @if($errors->has('totp_code') || session('open_2fa_setup'))
    if (btnSetup2fa && qrSetupZone) {
      btnSetup2fa.click();
    }
  @endif
});
</script>
@endpush
