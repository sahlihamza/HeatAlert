<?php

namespace Database\Seeders;

use App\Models\AvisPoint;
use App\Models\PointFraicheur;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class PointFraicheurSeeder extends Seeder
{
    public function run(): void
    {
        $zones = Zone::all()->keyBy('nom');

        $points = [
            [
                'nom'            => 'Parc Habib Thameur',
                'type'           => 'parc',
                'adresse'        => 'Avenue Habib Thameur, Tunis',
                'latitude'       => 36.8197,
                'longitude'      => 10.1662,
                'horaires'       => '6h00 – 20h00',
                'accessible_pmr' => true,
                'actif'          => true,
                'zone_nom'       => 'Zone Tunis Centre',
            ],
            [
                'nom'            => 'Salle Climatisée Municipale — El Menzah',
                'type'           => 'salle_climatisee',
                'adresse'        => 'Rue de l\'Indépendance, El Menzah VI',
                'latitude'       => 36.8469,
                'longitude'      => 10.1934,
                'horaires'       => '8h00 – 18h00, fermé le vendredi',
                'accessible_pmr' => true,
                'actif'          => true,
                'zone_nom'       => 'Zone Tunis Centre',
            ],
            [
                'nom'            => 'Fontaine de la Place 7 Novembre',
                'type'           => 'fontaine',
                'adresse'        => 'Place 7 Novembre, Sfax Ville',
                'latitude'       => 34.7412,
                'longitude'      => 10.7625,
                'horaires'       => '24h/24',
                'accessible_pmr' => false,
                'actif'          => true,
                'zone_nom'       => 'Zone Sfax',
            ],
            [
                'nom'            => 'Piscine Municipale de Sousse',
                'type'           => 'piscine',
                'adresse'        => 'Boulevard du 14 Janvier, Sousse',
                'latitude'       => 35.8288,
                'longitude'      => 10.6389,
                'horaires'       => '8h00 – 19h30',
                'accessible_pmr' => true,
                'actif'          => true,
                'zone_nom'       => 'Zone Sousse',
            ],
            [
                'nom'            => 'Bibliothèque Nationale de Tunis',
                'type'           => 'bibliotheque',
                'adresse'        => 'Avenue de la Liberté, Tunis',
                'latitude'       => 36.8257,
                'longitude'      => 10.1681,
                'horaires'       => '8h00 – 17h00, fermé le dimanche',
                'accessible_pmr' => true,
                'actif'          => true,
                'zone_nom'       => 'Zone Tunis Centre',
            ],
            [
                'nom'            => 'Parc du Belvédère',
                'type'           => 'parc',
                'adresse'        => 'Rue de Belvédère, Tunis',
                'latitude'       => 36.8268,
                'longitude'      => 10.1840,
                'horaires'       => '6h00 – 21h00',
                'accessible_pmr' => false,
                'actif'          => true,
                'zone_nom'       => 'Zone Tunis Centre',
            ],
            [
                'nom'            => 'Fontaine Borne d\'Eau — Gare Sfax',
                'type'           => 'fontaine',
                'adresse'        => 'Avenue Farhat Hached, Sfax',
                'latitude'       => 34.7300,
                'longitude'      => 10.7580,
                'horaires'       => '24h/24',
                'accessible_pmr' => true,
                'actif'          => true,
                'zone_nom'       => 'Zone Sfax',
            ],
            [
                'nom'            => 'Espace Frais — Mairie de Nabeul',
                'type'           => 'salle_climatisee',
                'adresse'        => 'Rue de la Municipalité, Nabeul',
                'latitude'       => 36.4512,
                'longitude'      => 10.7357,
                'horaires'       => '9h00 – 15h00 (jours ouvrables)',
                'accessible_pmr' => true,
                'actif'          => true,
                'zone_nom'       => null,
            ],
        ];

        foreach ($points as $data) {
            $zoneNom = $data['zone_nom'];
            unset($data['zone_nom']);

            $zone = $zoneNom ? $zones->get($zoneNom) : null;
            $data['zone_id'] = $zone?->id;

            PointFraicheur::create($data);
        }

        // Ajouter des avis de démonstration
        $allPoints = PointFraicheur::all();
        $users = User::where('role', 'ROLE_USER')->take(5)->get();

        if ($users->isEmpty()) {
            return;
        }

        $commentaires = [
            'Très agréable, endroit parfait pour se rafraîchir en été.',
            'Bien entretenu, accessible à tous. Je recommande !',
            'Eau fraîche, personnel sympa. Idéal pour les enfants.',
            'Propre et ombragé. Parfait pendant les vagues de chaleur.',
            'Bien situé mais parfois bondé en période estivale.',
            'Excellent point de fraîcheur, très bien aménagé.',
            'Utile mais mériterait plus d\'entretien.',
        ];

        foreach ($allPoints->take(4) as $point) {
            foreach ($users->take(3) as $i => $user) {
                AvisPoint::create([
                    'note'              => rand(3, 5),
                    'commentaire'       => $commentaires[array_rand($commentaires)],
                    'date_avis'         => now()->subDays(rand(1, 30))->toDateString(),
                    'user_id'           => $user->id,
                    'point_fraicheur_id' => $point->id,
                ]);
            }
        }
    }
}
