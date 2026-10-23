<?php

namespace Database\Seeders;

use App\Models\EquipementSensible;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class EquipementSensibleSeeder extends Seeder
{
    /**
     * Exécute le seed des équipements sensibles.
     */
    public function run(): void
    {
        $zones = Zone::all()->keyBy('nom');
        $citizen = User::where('role', User::ROLE_USER)->first() ?? User::where('email', '!=', 'admin@admin.com')->first();
        $citizenId = $citizen?->id;

        $equipements = [
            // ─── RÉFRIGÉRATEURS & CHAMBRES FROIDES ───
            [
                'nom'                => 'Réfrigérateur à vaccins — Dispensaire Bab Souika',
                'type'               => 'frigo',
                'niveau_sensibilite' => 'eleve',
                'description'        => 'Stock de vaccins pédiatriques et sérums anti-rabiques. Température critique entre +2°C et +8°C. Ne pas ouvrir la porte en cas de coupure. Autonomie thermique estimée à 4h sans alimentation.',
                'adresse'            => '14 Rue Sidi Mahrez, Bab Souika, Tunis',
                'zone_nom'           => 'Zone Tunis Centre',
                'actif'              => true,
                'user_id'            => null,
            ],
            [
                'nom'                => 'Chambre froide de conservation des dérivés sanguins',
                'type'               => 'frigo',
                'niveau_sensibilite' => 'eleve',
                'description'        => 'Conservation des concentrés de globules rouges et plasma frais congelé. Alarme thermique déclenchée au-delà de +6°C. Basculement sur groupe électrogène de secours en moins de 15 minutes.',
                'adresse'            => 'Boulevard 9 Avril 1938, Bab Saadoun, Tunis',
                'zone_nom'           => 'Zone Tunis Centre',
                'actif'              => true,
                'user_id'            => $citizenId,
            ],
            [
                'nom'                => 'Réfrigérateur domestique de conservation d’insuline',
                'type'               => 'frigo',
                'niveau_sensibilite' => 'eleve',
                'description'        => 'Insuline pour patient diabétique insulinodépendant (type 1). En cas de coupure de plus de 2 heures, transférer dans une glacière isotherme avec accumulateurs de froid.',
                'adresse'            => 'Avenue Habib Bourguiba, Résidence Les Fleurs, Appt 3B',
                'zone_nom'           => 'Zone Tunis Centre',
                'actif'              => true,
                'user_id'            => $citizenId,
            ],
            [
                'nom'                => 'Chambre froide pharmacie de garde Sfax',
                'type'               => 'frigo',
                'niveau_sensibilite' => 'moyen',
                'description'        => 'Stock d’antibiotiques injectables et collyres thermosensibles. Maintenir fermé et surveiller le thermomètre digital toutes les 2 heures en période de canicule.',
                'adresse'            => 'Route de Téniour Km 1.5, Sfax',
                'zone_nom'           => 'Zone Sfax Centre',
                'actif'              => true,
                'user_id'            => null,
            ],

            // ─── MÉDICAMENTS THERMOSENSIBLES ───
            [
                'nom'                => 'Armoire réfrigérée insulines et hormones de croissance',
                'type'               => 'medicament',
                'niveau_sensibilite' => 'eleve',
                'description'        => 'Médicaments de haute sensibilité ne tolérant aucun dépassement de +25°C. Rupture de la chaîne du froid entraîne l’inactivation immédiate des principes actifs.',
                'adresse'            => 'Rue de la Liberté, Centre Médical El Menzah VI',
                'zone_nom'           => 'Zone El Menzah',
                'actif'              => true,
                'user_id'            => $citizenId,
            ],
            [
                'nom'                => 'Réserve de sérums anti-venin et antivarioliques',
                'type'               => 'medicament',
                'niveau_sensibilite' => 'eleve',
                'description'        => 'Sérums d’urgence vitale pour morsures et envenimations. À conserver impérativement à l’abri de la lumière et entre +2°C et +8°C.',
                'adresse'            => 'Hôpital Régional de Kairouan, Service Urgences',
                'zone_nom'           => 'Zone Kairouan Centre',
                'actif'              => true,
                'user_id'            => null,
            ],
            [
                'nom'                => 'Collyres et traitements ophtalmiques post-opératoires',
                'type'               => 'medicament',
                'niveau_sensibilite' => 'moyen',
                'description'        => 'Traitements sensibles à la chaleur et à la lumière. À conserver dans un sac isotherme dès que la température ambiante dépasse 35°C.',
                'adresse'            => 'Avenue Léopold Senghor, Sousse',
                'zone_nom'           => 'Zone Sousse Nord',
                'actif'              => true,
                'user_id'            => null,
            ],

            // ─── APPAREILS MÉDICAUX D’ASSISTANCE ───
            [
                'nom'                => 'Concentrateur d’oxygène à domicile (Assistance BPCO)',
                'type'               => 'appareil_medical',
                'niveau_sensibilite' => 'eleve',
                'description'        => 'Appareil vital pour patient sous oxygénothérapie continue. Autonomie de la batterie de secours : 90 minutes. Contacter la protection civile (198) en cas de coupure prolongée.',
                'adresse'            => 'Rue Ibn Khaldoun, Immeuble Ennasr, Bizerte',
                'zone_nom'           => 'Zone Bizerte Ville',
                'actif'              => true,
                'user_id'            => $citizenId,
            ],
            [
                'nom'                => 'Générateur d’hémodialyse péritonéale automatisée',
                'type'               => 'appareil_medical',
                'niveau_sensibilite' => 'eleve',
                'description'        => 'Cycle nocturne de dialyse péritonéale. Risque d’arrêt de cycle en cas de coupure électrique intempestive. Onduleur connecté (autonomie 45 min).',
                'adresse'            => 'Rue de Marseille, Tunis Centre',
                'zone_nom'           => 'Zone Tunis Centre',
                'actif'              => true,
                'user_id'            => $citizenId,
            ],
            [
                'nom'                => 'Moniteur cardio-respiratoire néonatal à domicile',
                'type'               => 'appareil_medical',
                'niveau_sensibilite' => 'eleve',
                'description'        => 'Surveillance du rythme cardiaque et de la saturation O2 pour nourrisson prématuré. Batterie lithium avec avertisseur sonore en cas de sous-tension.',
                'adresse'            => 'Avenue Hédi Nouira, Ennasr 2',
                'zone_nom'           => 'Zone El Menzah',
                'actif'              => true,
                'user_id'            => null,
            ],
            [
                'nom'                => 'Matelas à air motorisé anti-escarres (Patient grabataire)',
                'type'               => 'appareil_medical',
                'niveau_sensibilite' => 'faible',
                'description'        => 'Compresseur alternatif pour matelas médicalisé. En cas de coupure, basculer manuellement le patient toutes les 2 heures pour prévenir les escarres.',
                'adresse'            => 'Rue Mongi Slim, Nabeul',
                'zone_nom'           => 'Zone Nabeul Plage',
                'actif'              => true,
                'user_id'            => null,
            ],

            // ─── VENTILATEURS & SYSTÈMES DE RAFRAÎCHISSEMENT ───
            [
                'nom'                => 'Brumisateur et ventilateur d’urgence — Crèche Les Poussins',
                'type'               => 'ventilateur',
                'niveau_sensibilite' => 'moyen',
                'description'        => 'Système de brumisation et ventilation pour salle de sieste des enfants de moins de 3 ans lors des pics de canicule niveau rouge ou orange.',
                'adresse'            => 'Avenue Habib Bourguiba, La Marsa',
                'zone_nom'           => 'Zone La Marsa',
                'actif'              => true,
                'user_id'            => null,
            ],
            [
                'nom'                => 'Système de ventilation filtrée — EHPAD Les Oliviers',
                'type'               => 'ventilateur',
                'niveau_sensibilite' => 'moyen',
                'description'        => 'Renouvellement d’air et rafraîchissement continu de la salle commune pour 45 résidents âgés vulnérables. Vérifier les filtres antipoussière chaque semaine.',
                'adresse'            => 'Route de Gabès Km 3, Sfax',
                'zone_nom'           => 'Zone Sfax Sud',
                'actif'              => true,
                'user_id'            => null,
            ],
            [
                'nom'                => 'Ventilateur sur pied autonome à batterie rechargeable',
                'type'               => 'ventilateur',
                'niveau_sensibilite' => 'faible',
                'description'        => 'Ventilateur d’appoint rechargeable pour personne âgée vivant seule. Autonomie de 8 heures en vitesse moyenne. À charger systématiquement la nuit.',
                'adresse'            => 'Rue Taieb Mhiri, Monastir',
                'zone_nom'           => 'Zone Monastir Ville',
                'actif'              => false,
                'user_id'            => $citizenId,
            ],
        ];

        $inserted = 0;

        foreach ($equipements as $item) {
            $zoneId = null;
            if (!empty($item['zone_nom'])) {
                $zone = $zones->get($item['zone_nom']) ?? Zone::where('nom', 'like', '%' . $item['zone_nom'] . '%')->first();
                $zoneId = $zone?->id;
            }

            // Fallback si la zone spécifique n'est pas trouvée : première zone disponible
            if (!$zoneId && $zones->isNotEmpty()) {
                $zoneId = $zones->first()->id;
            }

            EquipementSensible::updateOrCreate(
                [
                    'nom' => $item['nom'],
                ],
                [
                    'type'               => $item['type'],
                    'niveau_sensibilite' => $item['niveau_sensibilite'],
                    'description'        => $item['description'],
                    'adresse'            => $item['adresse'],
                    'zone_id'            => $zoneId,
                    'actif'              => $item['actif'],
                    'user_id'            => $item['user_id'],
                ]
            );

            $inserted++;
        }

        $this->command->info("✅ {$inserted} équipements sensibles insérés ou mis à jour avec succès.");
    }
}
