<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | HeatAlert Admin</title>
    <meta name="description" content="HeatAlert - Panneau d'administration des alertes météo en Tunisie"/>
    <link href="{{ asset('assets/admin/css/font-face.css') }}" rel="stylesheet" media="all"/>
    <link rel="preconnect" href="https://rsms.me/"/>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css"/>
    
  <!-- Favicons — Logo HeatAlert (Thermomètre-Soleil) -->
  <link href="{{ asset('assets/front/img/favicon-heatalert.jpg') }}" rel="icon" type="image/jpeg">
  <link href="{{ asset('assets/front/img/favicon-heatalert.jpg') }}" rel="apple-touch-icon">

    <link href="{{ asset('assets/admin/vendor/fontawesome-7.3.1/css/all.min.css') }}" rel="stylesheet" media="all"/>
    <link href="{{ asset('assets/admin/vendor/bootstrap-5.3.8.min.css') }}" rel="stylesheet" media="all"/>
    <link href="{{ asset('assets/admin/vendor/css-hamburgers/hamburgers.min.css') }}" rel="stylesheet" media="all"/>
    <link href="{{ asset('assets/admin/css/theme.css') }}" rel="stylesheet" media="all"/>
    <link href="{{ asset('assets/admin/css/app.css') }}" rel="stylesheet" media="all"/>
    <style>
      @keyframes toastSlideInRight {
        0% {
          transform: translateX(120%);
          opacity: 0;
        }
        70% {
          transform: translateX(-4%);
          opacity: 1;
        }
        100% {
          transform: translateX(0);
          opacity: 1;
        }
      }
      @keyframes toastSlideOutRight {
        0% {
          transform: translateX(0);
          opacity: 1;
        }
        100% {
          transform: translateX(120%);
          opacity: 0;
        }
      }
      .toast-slide-animate {
        animation: toastSlideInRight 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      }
      .toast-slide-animate.sliding-out {
        animation: toastSlideOutRight 0.35s cubic-bezier(0.7, 0, 0.84, 0) forwards !important;
      }
    </style>
    @stack('styles')
  </head>
  <body class="app">
    <a class="visually-hidden-focusable skip-link" href="#main-content">Aller au contenu principal</a>
    <div class="page-wrapper">

      @include('layouts.admin.components.headeradmin')

      <div class="page-container">

        @include('layouts.admin.components.asideadmin')

        <main class="main-content" id="main-content">
          <div class="section__content section__content--p30">
            <div class="container-fluid">

              {{-- Toast de suppression avec animation slide --}}
              @if(session('deleted'))
              <div id="toast-deleted-wrapper" class="position-fixed top-0 end-0 p-3" style="z-index: 99999; margin-top: 70px;">
                <div id="toast-deleted" class="toast toast-slide-animate show border-0 text-white shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: 14px; min-width: 320px; max-width: 420px; background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);">
                  <div class="d-flex align-items-center p-3">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; margin-right: 12px;">
                      <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <div class="flex-grow-1 me-2">
                      <div class="fw-bold" style="font-size: 0.92rem;">Suppression effectuée</div>
                      <div class="small opacity-90" style="font-size: 0.82rem;">{{ session('deleted') }}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white m-0" data-bs-dismiss="toast" aria-label="Fermer"></button>
                  </div>
                </div>
              </div>
              @endif

              {{-- Toast de succès avec animation slide --}}
              @if(session('success'))
              <div id="toast-success-wrapper" class="position-fixed top-0 end-0 p-3" style="z-index: 99999; margin-top: 70px;">
                <div id="toast-success" class="toast toast-slide-animate show border-0 text-white shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: 14px; min-width: 320px; max-width: 420px; background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
                  <div class="d-flex align-items-center p-3">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; margin-right: 12px;">
                      <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="flex-grow-1 me-2">
                      <div class="fw-bold" style="font-size: 0.92rem;">Opération réussie</div>
                      <div class="small opacity-90" style="font-size: 0.82rem;">{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white m-0" data-bs-dismiss="toast" aria-label="Fermer"></button>
                  </div>
                </div>
              </div>
              @endif

              @yield('content')

              {{-- Footer --}}
              <div class="row" style="margin-top: 28px;">
                <div class="col-md-12">
                  <div class="copyright">
                    <p>Copyright &copy; {{ date('Y') }} HeatAlert. Tous droits réservés.</p>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </main>
      </div>
    </div>

    <script src="{{ asset('assets/admin/js/vanilla-utils.js') }}"></script>
    <script src="{{ asset('assets/admin/vendor/bootstrap-5.3.8.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/admin/vendor/chartjs/chart.umd.js-4.5.1.min.js') }}"></script>
    <script>
      window.HEATALERT_COMMANDS = [
        { section: 'Navigation', title: 'Dashboard', sub: 'Tableau de bord et indicateurs', href: "{{ route('admin.dashboard') }}", icon: 'fa-gauge-high' },

        { section: 'Zones Géographiques', title: 'Liste des zones', sub: 'Consulter et gérer les zones de Tunisie', href: "{{ route('admin.zones.index') }}", icon: 'fa-map-location-dot' },
        { section: 'Zones Géographiques', title: 'Ajouter une zone', sub: 'Créer une nouvelle zone géographique', href: "{{ route('admin.zones.create') }}", icon: 'fa-plus' },

        { section: 'Alertes Météo', title: 'Liste des alertes', sub: 'Gérer les alertes de vigilance canicule', href: "{{ route('admin.alertes.index') }}", icon: 'fa-triangle-exclamation' },
        { section: 'Alertes Météo', title: 'Créer une alerte', sub: 'Publier une nouvelle alerte météo', href: "{{ route('admin.alertes.create') }}", icon: 'fa-plus' },
        { section: 'Équipements sensibles', title: 'Liste des équipements', sub: 'Gérer les équipements sensibles', href: "{{ route('admin.equipements-sensibles.index') }}", icon: 'fa-shield-heart' },
        { section: 'Équipements sensibles', title: 'Ajouter un équipement', sub: 'Référencer un site sensible', href: "{{ route('admin.equipements-sensibles.create') }}", icon: 'fa-plus' },

        { section: 'Coupures électriques', title: 'Signalements', sub: 'Modérer les signalements de coupures', href: "{{ route('admin.signalements.index') }}", icon: 'fa-flag' },

        { section: 'Conseils Prévention', title: 'Liste des conseils', sub: 'Consulter les recommandations santé', href: "{{ route('admin.conseils.index') }}", icon: 'fa-lightbulb' },
        { section: 'Conseils Prévention', title: 'Ajouter un conseil', sub: 'Publier un geste de prévention canicule', href: "{{ route('admin.conseils.create') }}", icon: 'fa-plus' },

        { section: 'Portail Citoyen', title: 'Voir le site public', sub: 'Accéder à la page d\'accueil HeatAlert', href: "{{ route('home') }}", icon: 'fa-globe' }
      ];
    </script>
    <script src="{{ asset('assets/admin/js/bootstrap5-init.js') }}"></script>
    <script src="{{ asset('assets/admin/js/main-vanilla.js') }}"></script>
    <script src="{{ asset('assets/admin/js/modern-plugins.js') }}"></script>

    {{-- Modal de Confirmation de Suppression Global --}}
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
          <div class="modal-header border-0 pb-0 pt-4 px-4 position-relative">
            <div class="d-flex align-items-center gap-3">
              <div style="width: 50px; height: 50px; border-radius: 14px; background: rgba(220, 38, 38, 0.12); color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
                <i class="fa-solid fa-triangle-exclamation"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold text-dark mb-0" id="deleteConfirmModalLabel" style="font-size: 1.15rem;">Confirmation</h5>
                <span class="text-danger small fw-semibold"><i class="fa-solid fa-circle-exclamation me-1"></i>Action irréversible</span>
              </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer" style="margin-top: -12px; margin-right: -4px;"></button>
          </div>
          <div class="modal-body px-4 py-3">
            <p class="mb-3 text-secondary" style="font-size: 0.95rem;">
              Êtes-vous certain de vouloir supprimer <span id="deleteModalTargetType">cet élément</span> ?
            </p>
            <div class="p-3 rounded-3 bg-light border border-light-subtle d-flex align-items-center gap-2">
              <i class="fa-solid fa-trash text-danger flex-shrink-0"></i>
              <span class="fw-bold text-dark text-truncate" id="deleteModalItemName" style="font-size: 0.92rem;">—</span>
            </div>
          </div>
          <div class="modal-footer border-0 px-4 pb-4 pt-1 d-flex gap-2">
            <button type="button" class="m-btn m-btn--ghost flex-grow-1 text-center py-2" data-bs-dismiss="modal">
              <i class="fa-solid fa-xmark me-2"></i>Annuler
            </button>
            <form id="deleteModalForm" method="POST" action="" class="flex-grow-1 m-0">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 shadow-sm" style="border-radius: 8px;">
                <i class="fa-solid fa-trash-can"></i> Supprimer
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    @include('admin.partials._moderation_confirm_modal')

    {{-- Auto-dismiss toasts avec slide-out et écouteur global du modal de suppression --}}
    <script>
      // Fonction globale pour afficher le modal de suppression
      window.openDeleteConfirmModal = function (actionUrl, itemName, itemType) {
        const modalEl = document.getElementById('deleteConfirmModal');
        if (!modalEl) return;

        const form = document.getElementById('deleteModalForm');
        const nameEl = document.getElementById('deleteModalItemName');
        const typeEl = document.getElementById('deleteModalTargetType');

        if (form) form.action = actionUrl;
        if (nameEl) nameEl.textContent = itemName || 'cet élément';
        if (typeEl) typeEl.textContent = itemType || 'cet élément';

        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();
      };

      // Écouteur global sur tous les éléments déclencheurs de suppression
      document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.js-trigger-delete');
        if (trigger) {
          e.preventDefault();
          e.stopPropagation();
          const action = trigger.getAttribute('data-action');
          const name = trigger.getAttribute('data-name');
          const type = trigger.getAttribute('data-type');
          window.openDeleteConfirmModal(action, name, type);
        }
      });

      // Gestion des toasts avec animation de sortie fluide
      document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.toast').forEach(function (toastEl) {
          const bsToast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4200 });
          bsToast.show();

          // Animation slide-out quand le toast se ferme
          toastEl.addEventListener('hide.bs.toast', function () {
            toastEl.classList.add('sliding-out');
          });
        });
      });
    </script>

    @stack('scripts')

  </body>
</html>