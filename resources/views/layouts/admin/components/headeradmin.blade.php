
<header class="header-mobile d-block d-lg-none">
  <div class="header-mobile__bar">
    <div class="container-fluid">
      <div class="header-mobile-inner">
        <a class="logo" href="{{ route('admin.dashboard') }}">
          <img src="{{ asset('assets/admin/images/icon/logo.png') }}" alt="HeatAlert">
        </a>
        <button class="hamburger hamburger--slider" type="button" aria-label="Basculer la navigation">
          <span class="hamburger-box"><span class="hamburger-inner"></span></span>
        </button>
      </div>
    </div>
  </div>
  <nav class="navbar-mobile">
    <div class="container-fluid">
      <ul class="navbar-mobile__list list-unstyled">
        <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
          <a href="{{ route('admin.dashboard') }}">
            <i class="fa-solid fa-gauge-high"></i>Dashboard
          </a>
        </li>
        <li class="{{ request()->routeIs('admin.zones.*') ? 'active has-sub' : 'has-sub' }}">
          <a class="js-arrow" href="#">
            <i class="fa-solid fa-map-location-dot"></i>Zones
          </a>
          <ul class="navbar-mobile-sub__list list-unstyled js-sub-list">
            <li><a href="{{ route('admin.zones.index') }}"><i class="fa-solid fa-list"></i>Liste des zones</a></li>
            <li><a href="{{ route('admin.zones.create') }}"><i class="fa-solid fa-plus"></i>Ajouter une zone</a></li>
          </ul>
        </li>
        <li class="{{ request()->routeIs('admin.predictions.*') ? 'active' : '' }}">
          <a href="{{ route('admin.predictions.index') }}">
            <i class="fa-solid fa-wand-magic-sparkles"></i>Prévisions de coupures
          </a>
        </li>
        <li class="{{ request()->routeIs('admin.alertes.*') ? 'active has-sub' : 'has-sub' }}">
          <a class="js-arrow" href="#">
            <i class="fa-solid fa-triangle-exclamation"></i>Alertes Météo
          </a>
          <ul class="navbar-mobile-sub__list list-unstyled js-sub-list">
            <li><a href="{{ route('admin.alertes.index') }}"><i class="fa-solid fa-list"></i>Liste des alertes</a></li>
            <li><a href="{{ route('admin.alertes.create') }}"><i class="fa-solid fa-plus"></i>Ajouter une alerte</a></li>
          </ul>
        </li>
        <li class="{{ request()->routeIs('admin.coupures.*') || request()->routeIs('admin.steg.*') ? 'active has-sub' : 'has-sub' }}">
          <a class="js-arrow" href="#">
            <i class="fa-solid fa-bolt"></i>Coupures
          </a>
          <ul class="navbar-mobile-sub__list list-unstyled js-sub-list">
            <li><a href="{{ route('admin.coupures.index') }}"><i class="fa-solid fa-list"></i>Liste des coupures</a></li>
            <li><a href="{{ route('admin.coupures.create') }}"><i class="fa-solid fa-plus"></i>Ajouter une coupure</a></li>
            <li><a href="{{ route('admin.steg.index') }}"><i class="fa-solid fa-plug-circle-bolt text-warning"></i>Sync STEG</a></li>
          </ul>
        </li>
        <li class="{{ request()->routeIs('admin.signalements.*') ? 'active' : '' }}">
          <a href="{{ route('admin.signalements.index') }}">
            <i class="fa-solid fa-flag"></i>Signalements
          </a>
        </li>
        <li class="{{ request()->routeIs('admin.points_fraicheur.*') || request()->routeIs('admin.avis_points.*') ? 'active has-sub' : 'has-sub' }}">
          <a class="js-arrow" href="#">
            <i class="fa-solid fa-snowflake"></i>Points de Fraîcheur
          </a>
          <ul class="navbar-mobile-sub__list list-unstyled js-sub-list">
            <li><a href="{{ route('admin.points_fraicheur.index') }}"><i class="fa-solid fa-list"></i>Liste des points</a></li>
            <li><a href="{{ route('admin.points_fraicheur.create') }}"><i class="fa-solid fa-plus"></i>Ajouter un point</a></li>
            <li><a href="{{ route('admin.avis_points.index') }}"><i class="fa-solid fa-star-half-stroke"></i>Supervision des avis</a></li>
          </ul>
        </li>
        <li class="{{ request()->routeIs('admin.conseils.*') ? 'active has-sub' : 'has-sub' }}">
          <a class="js-arrow" href="#">
            <i class="fa-solid fa-lightbulb"></i>Conseils
          </a>
          <ul class="navbar-mobile-sub__list list-unstyled js-sub-list">
            <li><a href="{{ route('admin.conseils.index') }}"><i class="fa-solid fa-list"></i>Liste des conseils</a></li>
            <li><a href="{{ route('admin.conseils.create') }}"><i class="fa-solid fa-plus"></i>Ajouter un conseil</a></li>
          </ul>
        </li>
        <li class="{{ request()->routeIs('admin.statistiques') ? 'active' : '' }}">
          <a href="{{ route('admin.statistiques') }}">
            <i class="fa-solid fa-chart-line"></i>Statistiques
          </a>
        </li>
        <li class="{{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
          <a href="{{ route('admin.profile.edit') }}">
            <i class="fa-solid fa-user-shield"></i>Profil & Sécurité
          </a>
        </li>
      </ul>
    </div>
  </nav>
</header>

<aside class="menu-sidebar" id="main-sidebar">
  <div class="logo">
    <a class="logo-link" href="{{ route('admin.dashboard') }}" aria-label="HeatAlert home">
      <span class="logo-mark" aria-hidden="true">H</span>
      <span class="logo-text">HeatAlert Admin</span>
    </a>
    <button class="sidebar-close js-sidebar-toggle" type="button" aria-label="Fermer la navigation">
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
  </div>

  <div class="menu-sidebar__content js-scrollbar1">
    <nav class="navbar-sidebar">
      <ul class="list-unstyled navbar__list">

        {{-- Dashboard --}}
        <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
          <a href="{{ route('admin.dashboard') }}">
            <i class="fa-solid fa-gauge-high"></i>Dashboard
          </a>
        </li>

        {{-- Zones --}}
        <li class="{{ request()->routeIs('admin.zones.*') ? 'active has-sub' : 'has-sub' }}">
          <a class="js-arrow {{ request()->routeIs('admin.zones.*') ? 'open' : '' }}" href="#">
            <i class="fa-solid fa-map-location-dot"></i>Zones
          </a>
          <ul class="list-unstyled navbar__sub-list js-sub-list" {{ request()->routeIs('admin.zones.*') ? 'style=display:block' : '' }}>
            <li class="{{ request()->routeIs('admin.zones.index') ? 'active' : '' }}">
              <a href="{{ route('admin.zones.index') }}">Liste des zones</a>
            </li>
            <li class="{{ request()->routeIs('admin.zones.create') ? 'active' : '' }}">
              <a href="{{ route('admin.zones.create') }}">Ajouter une zone</a>
            </li>
          </ul>
        </li>

        <li class="{{ request()->routeIs('admin.predictions.*') ? 'active' : '' }}">
          <a href="{{ route('admin.predictions.index') }}">
            <i class="fa-solid fa-wand-magic-sparkles"></i>Prévisions de coupures
          </a>
        </li>

        {{-- Alertes Météo --}}
        <li class="{{ request()->routeIs('admin.alertes.*') ? 'active has-sub' : 'has-sub' }}">
          <a class="js-arrow {{ request()->routeIs('admin.alertes.*') ? 'open' : '' }}" href="#">
            <i class="fa-solid fa-triangle-exclamation"></i>Alertes Météo
          </a>
          <ul class="list-unstyled navbar__sub-list js-sub-list" {{ request()->routeIs('admin.alertes.*') ? 'style=display:block' : '' }}>
            <li class="{{ request()->routeIs('admin.alertes.index') ? 'active' : '' }}">
              <a href="{{ route('admin.alertes.index') }}">Liste des alertes</a>
            </li>
            <li class="{{ request()->routeIs('admin.alertes.create') ? 'active' : '' }}">
              <a href="{{ route('admin.alertes.create') }}">Ajouter une alerte</a>
            </li>
          </ul>
        </li>

        {{-- Équipements sensibles --}}
        <li class="{{ request()->routeIs('admin.equipements-sensibles.*') ? 'active has-sub' : 'has-sub' }}">
          <a class="js-arrow {{ request()->routeIs('admin.equipements-sensibles.*') ? 'open' : '' }}" href="#">
            <i class="fa-solid fa-shield-heart"></i>Équipements sensibles
          </a>
          <ul class="list-unstyled navbar__sub-list js-sub-list" {{ request()->routeIs('admin.equipements-sensibles.*') ? 'style=display:block' : '' }}>
            <li><a href="{{ route('admin.equipements-sensibles.index') }}">Liste des équipements</a></li>
            <li><a href="{{ route('admin.equipements-sensibles.create') }}">Ajouter un équipement</a></li>
          </ul>
        </li>

                {{-- Conseils --}}
        <li class="{{ request()->routeIs('admin.conseils.*') ? 'active has-sub' : 'has-sub' }}">
          <a class="js-arrow {{ request()->routeIs('admin.conseils.*') ? 'open' : '' }}" href="#">
            <i class="fa-solid fa-lightbulb"></i>Conseils
          </a>
          <ul class="list-unstyled navbar__sub-list js-sub-list" {{ request()->routeIs('admin.conseils.*') ? 'style=display:block' : '' }}>
            <li class="{{ request()->routeIs('admin.conseils.index') ? 'active' : '' }}">
              <a href="{{ route('admin.conseils.index') }}">Liste des conseils</a>
            </li>
            <li class="{{ request()->routeIs('admin.conseils.create') ? 'active' : '' }}">
              <a href="{{ route('admin.conseils.create') }}">Ajouter un conseil</a>
            </li>
          </ul>
        </li>

        {{-- Coupures électriques --}}
        <li class="{{ request()->routeIs('admin.coupures.*') || request()->routeIs('admin.steg.*') ? 'active has-sub' : 'has-sub' }}">
          <a class="js-arrow {{ request()->routeIs('admin.coupures.*') || request()->routeIs('admin.steg.*') ? 'open' : '' }}" href="#">
            <i class="fa-solid fa-bolt"></i>Coupures
          </a>
          <ul class="list-unstyled navbar__sub-list js-sub-list" {{ request()->routeIs('admin.coupures.*') || request()->routeIs('admin.steg.*') ? 'style=display:block' : '' }}>
            <li class="{{ request()->routeIs('admin.coupures.index') ? 'active' : '' }}">
              <a href="{{ route('admin.coupures.index') }}">Liste des coupures</a>
            </li>
            <li class="{{ request()->routeIs('admin.coupures.create') ? 'active' : '' }}">
              <a href="{{ route('admin.coupures.create') }}">Ajouter une coupure</a>
            </li>
            <li class="{{ request()->routeIs('admin.steg.*') ? 'active' : '' }}">
              <a href="{{ route('admin.steg.index') }}"><i class="fa-solid fa-plug-circle-bolt text-warning me-1"></i>Sync STEG Officielle</a>
            </li>
          </ul>
        </li>

        {{-- Signalements de coupures --}}
        <li class="{{ request()->routeIs('admin.signalements.*') ? 'active' : '' }}">
          <a href="{{ route('admin.signalements.index') }}">
            <i class="fa-solid fa-flag"></i>Signalements
          </a>
        </li>

        {{-- Points de Fraîcheur --}}
        <li class="{{ request()->routeIs('admin.points_fraicheur.*') || request()->routeIs('admin.avis_points.*') ? 'active has-sub' : 'has-sub' }}">
          <a class="js-arrow {{ request()->routeIs('admin.points_fraicheur.*') || request()->routeIs('admin.avis_points.*') ? 'open' : '' }}" href="#">
            <i class="fa-solid fa-snowflake"></i>Points de Fraîcheur
          </a>
          <ul class="list-unstyled navbar__sub-list js-sub-list" {{ request()->routeIs('admin.points_fraicheur.*') || request()->routeIs('admin.avis_points.*') ? 'style=display:block' : '' }}>
            <li class="{{ request()->routeIs('admin.points_fraicheur.index') ? 'active' : '' }}">
              <a href="{{ route('admin.points_fraicheur.index') }}">Liste des points</a>
            </li>
            <li class="{{ request()->routeIs('admin.points_fraicheur.create') ? 'active' : '' }}">
              <a href="{{ route('admin.points_fraicheur.create') }}">Ajouter un point</a>
            </li>
            <li class="{{ request()->routeIs('admin.avis_points.index') ? 'active' : '' }}">
              <a href="{{ route('admin.avis_points.index') }}">Supervision des avis</a>
            </li>
          </ul>
        </li>

        {{-- Statistiques --}}
        <li class="{{ request()->routeIs('admin.statistiques') ? 'active' : '' }}">
          <a href="{{ route('admin.statistiques') }}">
            <i class="fa-solid fa-chart-line"></i>Statistiques
            @php
              $nbCritiques = \App\Models\AlerteMeteo::where('statut','active')->whereIn('niveau',['rouge','orange'])->count();
            @endphp
            @if($nbCritiques > 0)
              <span class="badge bg-danger rounded-pill ms-1" style="font-size: 10px;">{{ $nbCritiques }}</span>
            @endif
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
