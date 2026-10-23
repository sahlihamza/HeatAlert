  <header id="header" class="header fixed-top" style="background-color: #ffffff; box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);">

    <div class="topbar d-flex align-items-center">
      <div class="container d-flex justify-content-center justify-content-md-between align-items-center">
        <div class="contact-info d-flex align-items-center">
          <i class="bi bi-telephone-fill d-flex align-items-center me-3">
            <span class="text-white-50 me-1">Urgences:</span>
            <a href="tel:198" class="fw-bold text-white">198</a>
            <span class="mx-2 text-white-50">|</span>
            <a href="tel:190" class="fw-bold text-white">190</a>
          </i>
          <i class="bi bi-envelope d-flex align-items-center ms-3 d-none d-sm-flex">
            <a href="mailto:contact@heatalert.tn" class="text-white">contact@heatalert.tn</a>
          </i>
        </div>
        <div class="social-links d-none d-md-flex align-items-center gap-3">
          @auth
            <span class="text-white fw-semibold" style="font-size: 13px;">
              <i class="bi bi-person-circle me-1"></i>
              {{ auth()->user()->prenom ?? '' }} {{ auth()->user()->nom }}
            </span>
            @if(auth()->user()->isAdmin())
              <a href="{{ route('admin.dashboard') }}" class="text-white text-decoration-none" style="font-size: 12px; background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 20px;">
                <i class="bi bi-speedometer2 me-1"></i>Admin
              </a>
            @endif
            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
              @csrf
              <a href="{{ route('logout') }}"
                 onclick="event.preventDefault(); this.closest('form').submit();"
                 class="text-white text-decoration-none"
                 style="font-size: 12px; background: rgba(0,0,0,0.2); padding: 3px 10px; border-radius: 20px;">
                <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
              </a>
            </form>
          @else
            <a href="{{ route('login') }}" class="text-white text-decoration-none" style="font-size: 13px;">
              <i class="bi bi-box-arrow-in-right me-1"></i>Connexion
            </a>
            <a href="{{ route('register') }}" class="text-white text-decoration-none" style="font-size: 13px; background: rgba(255,255,255,0.25); padding: 3px 12px; border-radius: 20px;">
              Inscription
            </a>
          @endauth
        </div>
      </div>
    </div><!-- End Top Bar -->

    <div class="branding d-flex align-items-center" style="background-color: #ffffff;">

      <div class="container position-relative d-flex align-items-center justify-content-between">
        <a href="{{ route('front.home') }}" class="logo d-flex align-items-center text-decoration-none">
          <i class="bi bi-thermometer-sun me-2" style="font-size: 1.8rem; color: #dc2626;"></i>
          <h1 class="sitename mb-0" style="font-size: 26px; font-weight: 700; color: #1e3a5f;">Heat<span style="color: #dc2626;">Alert</span></h1>
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li>
              <a href="{{ route('front.home') }}" class="{{ request()->routeIs('front.home') ? 'active' : '' }}">
                Accueil
              </a>
            </li>
            <li>
              <a href="{{ route('front.zones.index') }}" class="{{ request()->routeIs('front.zones.*') ? 'active' : '' }}">
                Zones
              </a>
            </li>
            <li>
              <a href="{{ route('front.weather.global') }}" class="{{ request()->routeIs('front.weather.global') ? 'active' : '' }}">
                <i class="bi bi-globe2 me-1 text-danger"></i>Météo Mondiale
                <span class="badge bg-warning text-dark rounded-pill ms-1" style="font-size: 10px; padding: 2px 6px;">Live</span>
              </a>
            </li>
            @php $nbAlertes = \App\Models\AlerteMeteo::where('statut','active')->whereIn('niveau', ['rouge', 'orange'])->count(); @endphp
            <li class="dropdown">
              <a href="#" class="{{ request()->routeIs('front.alertes.*') || request()->routeIs('front.conseils.*') || request()->routeIs('front.coupures.*') || request()->routeIs('front.points_fraicheur.*') || request()->routeIs('front.fires.*') ? 'active' : '' }}">
                <span>Vigilance & Santé</span>
                @if($nbAlertes > 0)
                  <span class="badge bg-danger rounded-pill ms-1" style="font-size: 11px; padding: 2px 7px;">{{ $nbAlertes }}</span>
                @endif
                <i class="bi bi-chevron-down toggle-dropdown"></i>
              </a>
              <ul>
                <li>
                  <a href="{{ route('front.alertes.index') }}" class="d-flex align-items-center gap-2 {{ request()->routeIs('front.alertes.*') ? 'active' : '' }}">
                    <i class="bi bi-bell-fill text-danger"></i>
                     @if($nbAlertes > 0)
                      <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 10px;">{{ $nbAlertes }}</span>
                    @endif
                    <span>Alertes Météo</span>
                   
                  </a>
                </li>
                <li>
                  <a href="{{ route('front.conseils.index') }}" class="d-flex align-items-center gap-2 {{ request()->routeIs('front.conseils.*') ? 'active' : '' }}">
                    <i class="bi bi-heart-pulse-fill text-success"></i>
                    <span>Conseils Santé</span>
                  </a>
                </li>
                <li>
                  <a href="{{ route('front.coupures.index') }}" class="d-flex align-items-center gap-2 {{ request()->routeIs('front.coupures.*') ? 'active' : '' }}">
                    <i class="bi bi-lightning-charge-fill me-1 text-warning"></i>
                    <span>Coupures</span>
                  </a>
                </li>
                <li>
                  <a href="{{ route('front.points_fraicheur.index') }}" class="d-flex align-items-center gap-2 {{ request()->routeIs('front.points_fraicheur.*') ? 'active' : '' }}">
                    <i class="bi bi-snow text-info"></i>
                    <span>Points de Fraîcheur</span>
                  </a>
                </li>
                @auth
                <li>
                  <a href="{{ route('front.equipements_sensibles.index') }}" class="d-flex align-items-center gap-2 {{ request()->routeIs('front.equipements_sensibles.*') ? 'active' : '' }}">
                    <i class="bi bi-tools text-warning"></i>
                    <span>Préparation personnelle</span>
                  </a>
                </li>
                <li>
                  <a href="{{ route('front.module4_notifications.index') }}" class="d-flex align-items-center gap-2 {{ request()->routeIs('front.module4_notifications.*') ? 'active' : '' }}">
                    <i class="bi bi-envelope-heart text-danger"></i>
                    <span>Conseils par e-mail</span>
                  </a>
                </li>
                @endauth
                <li>
                  <a href="{{ route('front.fires.index') }}" class="d-flex align-items-center gap-2 {{ request()->routeIs('front.fires.*') ? 'active' : '' }}" title="Détecte les départs de feux près d'une zone via l'API NASA FIRMS">
                    <i class="bi bi-fire text-danger"></i>
                                        <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 9px; padding: 2px 6px;">NASA</span>

                    <span>Détection des Feux</span>
                  </a>
                </li>
              </ul>
            </li>

            @auth
            <li class="dropdown">
              <a href="#"><span>Mon Compte</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
              <ul>
                <li class="px-3 py-2 text-muted border-bottom" style="font-size: 13px;">
                  Connecté : <strong>{{ auth()->user()->prenom ?? '' }} {{ auth()->user()->nom }}</strong>
                </li>
                @if(auth()->user()->isAdmin())
                  <li><a href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Administration</a></li>
                @endif
                <li><a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}"><i class="bi bi-person-circle me-2 text-black"></i>Mon Profil</a></li>
                <li><a href="{{ route('front.zones.index') }}"><i class="bi bi-geo-alt me-2"></i>Mes Zones</a></li>
                <li><a href="{{ route('front.alertes.index') }}"><i class="bi bi-bell me-2"></i>Alertes Actives</a></li>
                <li><a href="{{ route('front.notifications.index') }}"><i class="bi bi-bell-fill me-2 text-danger"></i>Mes notifications @if(auth()->user()->unreadNotifications_count ?? auth()->user()->unreadNotifications()->count())<span class="badge bg-danger ms-1">{{ auth()->user()->unreadNotifications()->count() }}</span>@endif</a></li>
                <li><a href="{{ route('front.equipements_sensibles.index') }}"><i class="bi bi-shield-heart me-2 text-danger"></i>Ma préparation personnelle</a></li>
                <li><a href="{{ route('front.module4_notifications.index') }}"><i class="bi bi-envelope-heart me-2 text-danger"></i>Notifications de prévention</a></li>
                <li>
                  <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); this.closest('form').submit();">
                      <i class="bi bi-box-arrow-right me-2 text-danger"></i>Déconnexion
                    </a>
                  </form>
                </li>
              </ul>
            </li>
            @else
            <li><a href="{{ route('login') }}">Connexion</a></li>
            @endauth
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>

        <a class="cta-btn d-none d-lg-inline-flex align-items-center" href="tel:198" style="background: #dc2626; color: #ffffff; padding: 8px 18px; border-radius: 50px; font-weight: 600; font-size: 13px; text-decoration: none; transition: 0.3s; box-shadow: 0 4px 10px rgba(220,38,38,0.25);">
          <i class="bi bi-telephone-outbound-fill me-2"></i> Urgence 198
        </a>

      </div>

    </div>

  </header>