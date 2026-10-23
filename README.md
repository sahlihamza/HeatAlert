<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## ⚡ HeatAlert — Automatisation des Tâches Planifiées (Cron)

Le projet intègre des tâches planifiées autonomes 24/7 configurées dans `routes/console.php` :
1. **Prédictions de Coupures ML (`predictions:auto-check`)** : Analyse automatique de toutes les zones toutes les 6 heures avec alertes résidents et création de délestages préventifs si le risque > 70%.
2. **Synchronisation STEG (`steg:sync-coupures`)** : Importation et matching des avis de coupures officielles de la STEG toutes les 2 heures.

### 🚀 Lancement du Planificateur en Local (Développement)
Pour exécuter automatiquement les tâches planifiées pendant que votre application tourne :
```bash
php artisan schedule:work
```

### ⚙️ Déploiement en Production (Serveur Linux)
Ajoutez une seule entrée dans le `crontab` du serveur pour réveiller le planificateur chaque minute :
```bash
* * * * * cd /chemin-du-projet && php artisan schedule:run >> /dev/null 2>&1
```

### 🧪 Exécution Manuelle Immédiate
```bash
# Vérifier et notifier les zones critiques sans attendre 6h :
php artisan predictions:auto-check --threshold=0.70 --notify --auto-draft

# Forcer la synchronisation officielle STEG :
php artisan steg:sync-coupures --fresh
```

---

## 🤖 Microservice Machine Learning (FastAPI)

L'image Docker de production démarre Laravel et l'API FastAPI locale (`127.0.0.1:8001`). Pendant le build de l'image, `ml-service/bootstrap_synthetic_models.py` génère, entraîne et vérifie les artefacts des trois modèles (`artifacts/latest/`). Le build échoue si un modèle ne peut pas être chargé ou ne retourne pas une probabilité valide. Les artefacts ne sont pas enregistrés dans Git : ils sont créés dans l'image publiée sur GHCR.

Les modèles générés automatiquement sont des **prototypes d'amorçage entraînés sur des données synthétiques**. L'API le signale dans `/health`, `/ready` et les réponses de prédiction. Ils permettent de valider le fonctionnement technique des services, mais ne sont pas validés pour des décisions réelles. Pour une utilisation opérationnelle, réentraîner les modèles avec des historiques réels de coupures STEG, de propagation de feux et d'incidents d'équipements, puis remplacer les artefacts.

L'endpoint `/ready` vérifie que les trois fichiers de modèle sont chargés. L'endpoint `/health` expose leur état et indique `production_validated: false` pour les modèles bootstrap.

### 📦 Installation et Lancement du Microservice ML

1. **Ouvrir un terminal et naviguer vers le dossier du service :**
   ```bash
   cd ml-service
   ```

2. **Créer et activer l'environnement virtuel Python :**
   ```bash
   # Sous Windows PowerShell / CMD :
   python -m venv .venv
   .venv\Scripts\activate

   # Sous Linux / macOS :
   python3 -m venv .venv
   source .venv/bin/activate
   ```

3. **Installer les dépendances :**
   ```bash
   pip install -r requirements.txt
   ```

4. **(Optionnel) Réentraîner le modèle sur les données fraîches :**
   ```bash
   python train.py --artifacts-dir artifacts/latest
   ```
   *Cette commande applique le nettoyage, l'imputation médiane, la standardisation, le split chronologique et calibre le modèle avec génération du rapport d'évaluation `training_report.json`.*

5. **Lancer le serveur d'inférence FastAPI :**
   ```bash
   uvicorn app.main:app --port 8001 --reload
   ```
   *L'API sera accessible sur `http://127.0.0.1:8001` (Documentation interactive Swagger sur `http://127.0.0.1:8001/docs`).*

