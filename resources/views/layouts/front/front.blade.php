<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Index - HeatAlert</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons — Logo HeatAlert (Thermomètre-Soleil) -->
  <link href="{{ asset('assets/front/img/favicon-heatalert.jpg') }}" rel="icon" type="image/jpeg">
  <link href="{{ asset('assets/front/img/favicon-heatalert.jpg') }}" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href={{ asset("assets/front/vendor/bootstrap/css/bootstrap.min.css") }} rel="stylesheet">
  <link href={{ asset("assets/front/vendor/bootstrap-icons/bootstrap-icons.css") }} rel="stylesheet">
  <link href={{ asset("assets/front/vendor/aos/aos.css") }} rel="stylesheet">
  <link href={{ asset("assets/front/vendor/glightbox/css/glightbox.min.css") }} rel="stylesheet">
  <link href={{ asset("assets/front/vendor/fontawesome-free/css/all.min.css") }} rel="stylesheet">
  <link href={{ asset("assets/front/vendor/swiper/swiper-bundle.min.css") }} rel="stylesheet">

  <!-- Main CSS File -->
  <link href={{ asset("assets/front/css/main.css") }} rel="stylesheet">

  @stack('styles')

</head>

<body class="index-page">

  @include('layouts.front.components.headerfront')

  <main class="main">
    <br>
<br>


    @if(request()->routeIs('front.home') || request()->routeIs('home') || request()->is('/'))
      @yield('content')

      <section id="hero" class="hero section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="hero-content">
              <div class="trust-badges mb-4" data-aos="fade-right" data-aos-delay="200">
                <div class="badge-item">
                  <i class="bi bi-shield-check"></i>
                  <span>Alerte Précoce</span>
                </div>
                <div class="badge-item">
                  <i class="bi bi-clock-history"></i>
                  <span>Veille 24h/24</span>
                </div>
                <div class="badge-item">
                  <i class="bi bi-thermometer-sun"></i>
                  <span>Météo Tunisie</span>
                </div>
              </div>

              <h1 data-aos="fade-right" data-aos-delay="300">
                Alerte Canicule & <span class="highlight">Protection Santé</span> en Tunisie
              </h1>

              <p class="hero-description" data-aos="fade-right" data-aos-delay="400">
                Plateforme nationale de surveillance météorologique et d'alerte précoce. Suivez les vagues de chaleur en temps réel, anticipez les risques thermiques et adoptez les gestes essentiels pour protéger votre santé et vos proches.
              </p>

              <div class="hero-stats mb-4" data-aos="fade-right" data-aos-delay="500">
                <div class="stat-item">
                  <h3><span data-purecounter-start="0" data-purecounter-end="24" data-purecounter-duration="2"
                      class="purecounter"></span></h3>
                  <p>Gouvernorats suivis</p>
                </div>
                <div class="stat-item">
                  <h3><span data-purecounter-start="0" data-purecounter-end="100" data-purecounter-duration="2"
                      class="purecounter"></span>%</h3>
                  <p>Alertes en temps réel</p>
                </div>
                <div class="stat-item">
                  <h3><span data-purecounter-start="0" data-purecounter-end="24" data-purecounter-duration="2"
                      class="purecounter"></span>/7</h3>
                  <p>Veille sanitaire</p>
                </div>
              </div>

              <div class="hero-actions" data-aos="fade-right" data-aos-delay="600">
                <a href="#featured-departments" class="btn btn-primary">
                  <i class="fa-solid fa-triangle-exclamation me-2"></i>Consulter les Alertes
                </a>
                <a href="#find-a-doctor" class="btn btn-outline">
                  <i class="fa-solid fa-heart-pulse me-2"></i>Conseils de Prévention
                </a>
              </div>

              <div class="emergency-contact" data-aos="fade-right" data-aos-delay="700">
                <div class="emergency-icon">
                  <i class="bi bi-telephone-fill"></i>
                </div>
                <div class="emergency-info">
                  <small>Numéros d'Urgence Canicule</small>
                  <strong>198 (Protection Civile) · 190 (SAMU)</strong>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="hero-visual" data-aos="fade-left" data-aos-delay="400">
              <div class="main-image">
                <img src="{{ asset('assets/front/img/health/ha-surveillance.jpg') }}" alt="Surveillance HeatAlert Tunisie" class="img-fluid">
                <div class="floating-card appointment-card">
                  <div class="card-icon">
                    <i class="bi bi-thermometer-high text-danger"></i>
                  </div>
                  <div class="card-content">
                    <h6>Vigilance Chaleur</h6>
                    <p>Pic thermique actif</p>
                    <small>Tunisie · Relevés du jour</small>
                  </div>
                </div>
                <div class="floating-card rating-card">
                  <div class="card-content">
                    <div class="rating-stars text-warning">
                      <i class="bi bi-shield-fill-check"></i>
                      <i class="bi bi-shield-fill-check"></i>
                      <i class="bi bi-shield-fill-check"></i>
                      <i class="bi bi-shield-fill-check"></i>
                      <i class="bi bi-shield-fill-check"></i>
                    </div>
                    <h6>Sécurité 24/7</h6>
                    <small>Coordination Secours & Météo</small>
                  </div>
                </div>
              </div>
              <div class="background-elements">
                <div class="element element-1"></div>
                <div class="element element-2"></div>
                <div class="element element-3"></div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Hero Section -->

    <!-- Home About Section -->
    <section id="home-about" class="home-about section">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row align-items-center">
          <div class="col-lg-6 mb-5 mb-lg-0" data-aos="fade-right" data-aos-delay="200">
            <div class="about-content">
              <h2 class="section-heading">Protection Citoyenne Face aux Fortes Chaleurs</h2>
              <p class="lead-text">Face à l'intensification des vagues de chaleur estivales en Tunisie, HeatAlert s'engage à fournir une information météorologique préventive et des protocoles de sécurité sanitaire pour tous.</p>

              <p>Nos modèles météorologiques analysent les températures maximales, l'humidité relative, les indices UV et la vitesse du vent afin d'estimer avec précision le stress thermique réel. En coordination avec les autorités de protection civile et les structures médicales, nous déclenchons les alertes adaptées et diffusons les gestes de premiers secours pour prévenir l'insolation, la déshydratation et les malaises graves.</p>

              <div class="stats-grid">
                <div class="stat-item">
                  <div class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="24"
                    data-purecounter-duration="1"></div>
                  <div class="stat-label">Gouvernorats couverts</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="100"
                    data-purecounter-duration="1"></div>
                  <div class="stat-label">% Veille continue</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number purecounter" data-purecounter-start="0" data-purecounter-end="4"
                    data-purecounter-duration="1"></div>
                  <div class="stat-label">Niveaux de vigilance</div>
                </div>
              </div>

              <div class="cta-section">
                <a href="#featured-services" class="btn-primary">Découvrir le Système d'Alerte</a>
              </div>
            </div>
          </div>

          <div class="col-lg-6" data-aos="fade-left" data-aos-delay="300">
            <div class="about-visual">
              <div class="main-image">
                <img src={{ asset('assets/front/img/health/facilities-9.webp') }} alt="Centre de vigilance canicule" class="img-fluid">
              </div>
              <div class="floating-card">
                <div class="card-content">
                  <div class="icon">
                    <i class="bi bi-heart-pulse-fill text-danger"></i>
                  </div>
                  <div class="card-text">
                    <h4>Assistance & Alerte</h4>
                    <p>Protection active des personnes vulnérables</p>
                  </div>
                </div>
              </div>
              <div class="experience-badge">
                <div class="badge-content">
                  <span class="years">24h</span>
                  <span class="text">Surveillance Météo</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Home About Section -->

    <!-- Featured Departments Section -->
    <section id="featured-departments" class="featured-departments section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Domaines de Vigilance & Risques</h2>
        <p>Une surveillance multirisque des impacts environnementaux et sanitaires liés à la canicule en Tunisie.</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row g-5">

          <div class="col-lg-6" data-aos="zoom-in" data-aos-delay="100">
            <div class="specialty-card">
              <div class="specialty-content">
                <div class="specialty-meta">
                  <span class="specialty-label">Surveillance Thermique</span>
                </div>
                <h3>Alertes Canicule & Pics de Chaleur</h3>
                <p>Détection continue des vagues de chaleur avec seuils de vigilance adaptés (Vert, Jaune, Orange, Rouge) pour informer et protéger la population en temps réel.</p>
                <div class="specialty-features">
                  <span><i class="bi bi-check-circle-fill"></i>Calcul du stress thermique réel (Heat Index)</span>
                  <span><i class="bi bi-check-circle-fill"></i>Diffusion des alertes géociblées par région</span>
                </div>
                <a href="#featured-services" class="specialty-link">
                  En savoir plus sur les alertes <i class="bi bi-arrow-right"></i>
                </a>
              </div>
              <div class="specialty-visual">
                <img src={{ asset('assets/front/img/health/ha-services.jpg') }} alt="Dashboard de surveillance canicule INM Tunisie" class="img-fluid">
                <div class="visual-overlay">
                  <i class="bi bi-thermometer-sun"></i>
                </div>
              </div>
            </div>
          </div><!-- End Specialty Card -->

          <div class="col-lg-6" data-aos="zoom-in" data-aos-delay="200">
            <div class="specialty-card">
              <div class="specialty-content">
                <div class="specialty-meta">
                  <span class="specialty-label">Santé & Prévention</span>
                </div>
                <h3>Protection des Personnes Vulnérables</h3>
                <p>Protocoles d'action prioritaires pour les nourrissons, les personnes âgées, les malades chroniques et les travailleurs sur les chantiers extérieurs.</p>
                <div class="specialty-features">
                  <span><i class="bi bi-check-circle-fill"></i>Prévention de la déshydratation & insolation</span>
                  <span><i class="bi bi-check-circle-fill"></i>Recommandations médicales personnalisées</span>
                </div>
                <a href="#find-a-doctor" class="specialty-link">
                  Consulter les conseils santé <i class="bi bi-arrow-right"></i>
                </a>
              </div>
              <div class="specialty-visual">
                <img src={{ asset('assets/front/img/health/ha-protection.jpg') }} alt="Aide soignante et personne âgée durant la canicule en Tunisie" class="img-fluid">
                <div class="visual-overlay">
                  <i class="bi bi-shield-heart"></i>
                </div>
              </div>
            </div>
          </div><!-- End Specialty Card -->

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
            <div class="department-highlight">
              <div class="highlight-icon">
                <i class="bi bi-brightness-high text-warning"></i>
              </div>
              <h4>Indice UV & Rayonnement</h4>
              <p>Mesure quotidienne de l'intensité du rayonnement solaire pour prévenir les brûlures graves et les atteintes cutanées.</p>
              <ul class="highlight-list">
                <li>Échelle UV de 0 à 15+</li>
                <li>Heures de pointe à éviter (11h-16h)</li>
                <li>Protection solaire et lunettes adaptées</li>
              </ul>
              <a href="#find-a-doctor" class="highlight-cta">Consignes UV</a>
            </div>
          </div><!-- End Department Highlight -->

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
            <div class="department-highlight">
              <div class="highlight-icon">
                <i class="bi bi-lightning-charge " style="color: white ;"></i>
              </div>
              <h4>Réseau Électrique & Délestages</h4>
              <p>Anticipation des pics de charge énergétique dus à la climatisation pour limiter les risques de coupures de courant.</p>
              <ul class="highlight-list">
                <li>Suivi des pics de demande STEG</li>
                <li>Réglage optimal de climatisation (26°C)</li>
                <li>Préservation de la chaîne du froid</li>
              </ul>
              <a href="#featured-services" class="highlight-cta">Guide Énergie</a>
            </div>
          </div><!-- End Department Highlight -->

          <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
            <div class="department-highlight">
              <div class="highlight-icon">
                <i class="bi bi-fire text-warning"></i>
              </div>
              <h4>Vents de Sirocco & Feux de Forêt</h4>
              <p>Évaluation des vents chauds (Chehili) et de l'humidité critique pour prévenir les départs d'incendies estivaux.</p>
              <ul class="highlight-list">
                <li>Surveillance vents > 30 km/h</li>
                <li>Vigilance feux de forêts & végétation</li>
                <li>Coordination avec les gardes forestiers</li>
              </ul>
              <a href="#call-to-action" class="highlight-cta">Plan Incendie</a>
            </div>
          </div><!-- End Department Highlight -->

        </div>

        <div class="emergency-banner" data-aos="fade-up" data-aos-delay="400">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <div class="emergency-content">
                <h3>Assistance Médicale & Secours d'Urgence 24h/24</h3>
                <p>En cas de coup de chaleur grave (perte de connaissance, confusion, forte fièvre), placez la victime à l'ombre, humidifiez son corps et alertez immédiatement les secours.</p>
              </div>
            </div>
            <div class="col-lg-4 text-lg-end">
              <a href="tel:198" class="emergency-btn">
                <i class="bi bi-telephone-fill"></i>
                Protection Civile : 198 · SAMU : 190
              </a>
            </div>
          </div>
        </div>

      </div>

    </section><!-- /Featured Departments Section -->

    <!-- Featured Services Section -->
    <section id="featured-services" class="featured-services section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Services & Outils Préventifs</h2>
        <p>Des technologies modernes et des alertes anticipées pour sécuriser chaque foyer tunisien.</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row g-0">

          <div class="col-lg-8" data-aos="fade-right" data-aos-delay="200">
            <div class="featured-service-main">
              <div class="service-image-wrapper">
                <img src={{ asset('assets/front/img/health/ha-services.jpg') }} alt="Station météo en Tunisie sous chaleur extrême" class="img-fluid" loading="lazy">
                <div class="service-overlay">
                  <div class="service-badge">
                    <i class="bi bi-shield-check"></i>
                    <span>Alerte Précoce</span>
                  </div>
                </div>
              </div>
              <div class="service-details">
                <h2>Surveillance Météo Intelligente & Indice Ressenti</h2>
                <p>HeatAlert combine les observations météorologiques des stations tunisiennes (température maximale, taux d'humidité, vents) pour calculer le stress thermique réel subi par le corps humain et déclencher les alertes sanitaires adaptées.</p>
                <a href="#call-to-action" class="main-cta">Recevoir les Alertes Météo</a>
              </div>
            </div>
          </div>

          <div class="col-lg-4" data-aos="fade-left" data-aos-delay="300">
            <div class="services-sidebar">

              <div class="service-item" data-aos="fade-up" data-aos-delay="400">
                <div class="service-icon-wrapper">
                  <i class="bi bi-bell-fill text-warning"></i>
                </div>
                <div class="service-info">
                  <h4>Alertes Citoyennes en Direct</h4>
                  <p>Diffusion instantanée des avis de vigilance Orange et Rouge par gouvernorat et par ville.</p>
                  <a href="#featured-departments" class="service-link">Consulter les alertes</a>
                </div>
              </div>

              <div class="service-item" data-aos="fade-up" data-aos-delay="500">
                <div class="service-icon-wrapper">
                  <i class="bi bi-hospital text-danger"></i>
                </div>
                <div class="service-info">
                  <h4>Liaison Hôpitaux & SAMU</h4>
                  <p>Partage des bulletins de chaleur extrême avec les services d'urgence pour la mobilisation préventive.</p>
                  <a href="#call-to-action" class="service-link">Numéros d'urgence</a>
                </div>
              </div>

              <div class="service-item" data-aos="fade-up" data-aos-delay="600">
                <div class="service-icon-wrapper">
                  <i class="bi bi-geo-alt-fill text-primary"></i>
                </div>
                <div class="service-info">
                  <h4>Cartographie des Risques</h4>
                  <p>Visualisation précise des zones les plus chaudes et des seuils thermiques dépassés en Tunisie.</p>
                  <a href="#home-about" class="service-link">Voir la couverture</a>
                </div>
              </div>

            </div>
          </div>

        </div>

        <div class="specialties-grid" data-aos="fade-up" data-aos-delay="300">
          <div class="row align-items-center">

            <div class="col-lg-3 col-md-6">
              <div class="specialty-card">
                <div class="specialty-image">
                  <img src={{ asset('assets/front/img/health/ha-protection.jpg') }} alt="Hydratation et gestes santé canicule" class="img-fluid" loading="lazy">
                </div>
                <div class="specialty-content">
                  <h5>Hydratation Continue</h5>
                  <span>Boire régulièrement au moins 2L d'eau par jour</span>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="specialty-card">
                <div class="specialty-image">
                  <img src={{ asset('assets/front/img/health/ha-services.jpg') }} alt="Protection UV et station météorologique" class="img-fluid" loading="lazy">
                </div>
                <div class="specialty-content">
                  <h5>Protection UV Maximale</h5>
                  <span>Chapeaux, lunettes et vêtements en coton clair</span>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="specialty-card">
                <div class="specialty-image">
                  <img src={{ asset('assets/front/img/health/emergency-1.webp') }} alt="Maintien au frais" class="img-fluid" loading="lazy">
                </div>
                <div class="specialty-content">
                  <h5>Habitats Tempérés</h5>
                  <span>Fermer les volets le jour et aérer la nuit</span>
                </div>
              </div>
            </div>

            <div class="col-lg-3 col-md-6">
              <div class="specialty-card">
                <div class="specialty-image">
                  <img src={{ asset('assets/front/img/health/facilities-6.webp') }} alt="Solidarité" class="img-fluid" loading="lazy">
                </div>
                <div class="specialty-content">
                  <h5>Voisinage & Solidarité</h5>
                  <span>Prendre des nouvelles des proches et aînés isolés</span>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>

    </section><!-- /Featured Services Section -->

    <!-- Find A Doctor Section (Conseils & Gestes Santé des Experts) -->
    <section id="find-a-doctor" class="find-a-doctor section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <h2>Conseils & Gestes Santé des Experts</h2>
        <p>Recommandations validées par les professionnels de santé pour faire face aux vagues de chaleur en Tunisie.</p>
      </div><!-- End Section Title -->

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row justify-content-center mb-5" data-aos="fade-up" data-aos-delay="200">
          <div class="col-lg-8 text-center">
            <div class="search-section">
              <h3 class="search-title">Trouver une consigne adaptée à votre situation</h3>
              <p class="search-subtitle">Consultez les bonnes pratiques ciblées selon le profil des personnes exposées</p>
              <form class="search-form" action="#!" method="#" onsubmit="return false;">
                <div class="search-input-group">
                  <div class="input-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" class="form-control" name="doctor_name" placeholder="Ex: hydratation, bébé, personnes âgées, coup de chaleur...">
                  </div>
                  <div class="select-wrapper">
                    <i class="bi bi-filter"></i>
                    <select class="form-select" name="specialty">
                      <option value="">Toutes les catégories</option>
                      <option value="seniors">Seniors & Personnes Âgées</option>
                      <option value="enfants">Nourrissons & Enfants</option>
                      <option value="travail">Travail Extérieur & Chantiers</option>
                      <option value="sport">Sport & Activité Physique</option>
                      <option value="maladies">Maladies Chroniques</option>
                    </select>
                  </div>
                  <button type="button" class="search-btn">
                    <i class="bi bi-search"></i>
                    Rechercher
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="doctors-grid" data-aos="fade-up" data-aos-delay="300">
          <div class="doctor-profile" data-aos="zoom-in" data-aos-delay="100">
            <div class="profile-header">
              <div class="doctor-avatar">
                <img src={{ asset('assets/front/img/health/ha-doctor-sonia.jpg') }} alt="Dr. Sonia Ben Romdhane - Gériatrie CHU Tunis" class="img-fluid">
                <div class="status-indicator available"></div>
              </div>
              <div class="doctor-details">
                <h4>Dr. Sonia Ben Romdhane</h4>
                <span class="specialty-tag">Gériatrie · CHU Tunis</span>
                <div class="experience-info">
                  <i class="bi bi-award"></i>
                  <span>18 ans d'expérience</span>
                </div>
              </div>
            </div>
            <div class="rating-section">
              <div class="stars text-warning">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
              </div>
              <span class="rating-score">Priorité</span>
              <span class="review-count">Aînés & Seniors</span>
            </div>
            <p class="small text-muted px-3 mb-3" style="font-size: 0.84rem; line-height: 1.5;">
              « Ne pas attendre la soif pour boire. Proposer de l'eau fraîche toutes les 20 minutes et maintenir le contact quotidien avec les personnes isolées. »
            </p>
            <div class="action-buttons">
              <a href="#call-to-action" class="btn-primary w-100 text-center">Consignes Seniors</a>
            </div>
          </div><!-- End Doctor Profile -->

          <div class="doctor-profile" data-aos="zoom-in" data-aos-delay="200">
            <div class="profile-header">
              <div class="doctor-avatar">
                <img src={{ asset('assets/front/img/health/ha-doctor-karim.jpg') }} alt="Dr. Karim Mansour - Pédiatrie Tunis" class="img-fluid">
                <div class="status-indicator available"></div>
              </div>
              <div class="doctor-details">
                <h4>Dr. Karim Mansour</h4>
                <span class="specialty-tag">Pédiatrie · Hôpital d'Enfants</span>
                <div class="experience-info">
                  <i class="bi bi-award"></i>
                  <span>15 ans d'expérience</span>
                </div>
              </div>
            </div>
            <div class="rating-section">
              <div class="stars text-warning">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
              </div>
              <span class="rating-score">Vigilance</span>
              <span class="review-count">Bébés & Enfants</span>
            </div>
            <p class="small text-muted px-3 mb-3" style="font-size: 0.84rem; line-height: 1.5;">
              « Ne jamais laisser un enfant seul dans une voiture fermée, même pour 2 minutes. Éviter toute sortie entre 11h et 16h et hydrater régulièrement. »
            </p>
            <div class="action-buttons">
              <a href="#call-to-action" class="btn-primary w-100 text-center">Consignes Enfants</a>
            </div>
          </div><!-- End Doctor Profile -->

          <div class="doctor-profile" data-aos="zoom-in" data-aos-delay="300">
            <div class="profile-header">
              <div class="doctor-avatar">
                <img src={{ asset('assets/front/img/health/staff-3.webp') }} alt="Dr. Leila Trabelsi - Médecine du Travail" class="img-fluid">
                <div class="status-indicator available"></div>
              </div>
              <div class="doctor-details">
                <h4>Dr. Leila Trabelsi</h4>
                <span class="specialty-tag">Médecine du Travail</span>
                <div class="experience-info">
                  <i class="bi bi-award"></i>
                  <span>12 ans d'expérience</span>
                </div>
              </div>
            </div>
            <div class="rating-section">
              <div class="stars text-warning">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
              </div>
              <span class="rating-score">Chantiers</span>
              <span class="review-count">Travail Extérieur</span>
            </div>
            <p class="small text-muted px-3 mb-3" style="font-size: 0.84rem; line-height: 1.5;">
              « Aménager les horaires de travail physique pour débuter tôt le matin. Prévoir des zones d'ombre ventilées et au moins 3L d'eau fraîche par employé. »
            </p>
            <div class="action-buttons">
              <a href="#call-to-action" class="btn-primary w-100 text-center">Consignes Travail</a>
            </div>
          </div><!-- End Doctor Profile -->


        </div>

        <div class="text-center mt-5" data-aos="fade-up" data-aos-delay="700">
          <a href="#featured-departments" class="btn-view-all">
            Consulter la Carte des Alertes Météo
            <i class="bi bi-arrow-right"></i>
          </a>
        </div>

      </div>

    </section><!-- /Find A Doctor Section -->

    <!-- Call To Action Section -->
    <section id="call-to-action" class="call-to-action section light-background">

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="hero-content">
          <div class="row align-items-center">

            <div class="col-lg-6">
              <div class="content-wrapper" data-aos="fade-up" data-aos-delay="200">
                <h1>Adoptez les Bons Réflexes Face à la Chaleur</h1>
                <p>La prévention et la réactivité sauvent des vies. Restez informé des alertes météo de votre gouvernorat et appliquez les gestes essentiels pour vous protéger et veiller sur votre entourage.</p>

                <div class="cta-wrapper">
                  <a href="#featured-departments" class="primary-cta">
                    <span>Voir les Alertes Météo</span>
                    <i class="bi bi-arrow-right"></i>
                  </a>
                  <a href="#find-a-doctor" class="secondary-cta">
                    <span>Conseils Santé</span>
                    <i class="bi bi-arrow-right"></i>
                  </a>
                </div>
              </div>
            </div>

            <div class="col-lg-6">
              <div class="image-container" data-aos="fade-left" data-aos-delay="300">
                <img src={{ asset('assets/front/img/health/emergency-2.webp') }} alt="Station de surveillance météorologique HeatAlert Tunisie" class="img-fluid">
              </div>
            </div>

          </div>
        </div>

        <div class="features-section">

          <div class="row g-0">

            <div class="col-lg-4">
              <div class="feature-block" data-aos="fade-up" data-aos-delay="200">
                <div class="feature-icon">
                  <i class="bi bi-shield-check"></i>
                </div>
                <h3>Veille Météorologique Active</h3>
                <p>Surveillance continue des températures maximales, de l'humidité relative, des vents et de l'indice de stress thermique.</p>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="feature-block" data-aos="fade-up" data-aos-delay="300">
                <div class="feature-icon">
                  <i class="bi bi-clock"></i>
                </div>
                <h3>Disponibilité Secours 24/7</h3>
                <p>Coordination directe avec la Protection Civile (198) et le SAMU (190) pour les interventions d'urgence.</p>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="feature-block" data-aos="fade-up" data-aos-delay="400">
                <div class="feature-icon">
                  <i class="bi bi-people"></i>
                </div>
                <h3>Solidarité Communautaire</h3>
                <p>Protocoles clairs et fiches pratiques accessibles à tous les citoyens et soignants à travers la Tunisie.</p>
              </div>
            </div>

          </div>

        </div>

        <div class="contact-block">
          <div class="row">

            <div class="col-lg-8">
              <div class="contact-content" data-aos="fade-up" data-aos-delay="200">
                <h2>Une Urgence Médicale Liée à la Chaleur ?</h2>
                <p>En cas de malaise, déshydratation aiguë ou coup de chaleur chez un proche, contactez immédiatement les secours disponibles jour et nuit.</p>
              </div>
            </div>

            <div class="col-lg-4">
              <div class="contact-actions" data-aos="fade-up" data-aos-delay="300">
                <a href="tel:198" class="emergency-call">
                  <i class="bi bi-telephone"></i>
                  <span>Protection Civile : 198</span>
                </a>
                <a href="tel:190" class="contact-link">SAMU : 190</a>
              </div>
            </div>

          </div>
        </div>

      </div>

    </section><!-- /Call To Action Section -->
    @else
      @yield('content')
    @endif

  </main>


@include('layouts.front.components.footer') 


  <!-- Scroll Top -->
  <a href="#!" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
      class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src={{ asset("assets/front/vendor/bootstrap/js/bootstrap.bundle.min.js") }}></script>
  <script src={{ asset("assets/front/vendor/php-email-form/validate.js") }}></script>
  <script src={{ asset("assets/front/vendor/aos/aos.js") }}></script>
  <script src={{ asset("assets/front/vendor/glightbox/js/glightbox.min.js") }}></script>
  <script src={{ asset("assets/front/vendor/purecounter/purecounter_vanilla.js") }}></script>
  <script src={{ asset("assets/front/vendor/swiper/swiper-bundle.min.js") }}></script>

  <!-- Main JS File -->
  <script src={{ asset("assets/front/js/main.js") }}></script>

  @stack('scripts')

  <style>
    /* Évite le chevauchement avec la bulle de chatbot HeatBot */
    .scroll-top {
      bottom: 105px !important;
      right: 28px !important;
      z-index: 9999 !important;
    }
  </style>

  <!-- Widget Chatbot IA HeatAlert (Gemini Flash + Dynamic Data + Charts) -->
  @include('layouts.front.components.chatbot')

</body>

</html>
