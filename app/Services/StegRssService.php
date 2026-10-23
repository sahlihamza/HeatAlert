<?php

namespace App\Services;

use App\Models\Coupure;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'intégration STEG (Société Tunisienne de l'Électricité et du Gaz).
 *
 * Ce service interroge plusieurs sources publiques STEG pour détecter les
 * coupures programmées officielles et les importer automatiquement dans
 * la base HeatAlert en les croisant avec les zones existantes.
 *
 * Sources utilisées (par ordre de priorité) :
 *  1. RSS général STEG  → https://www.steg.com.tn/fr/rss.xml
 *  2. Page interruptions → https://www.steg.com.tn/fr/interruptions-de-service
 *  3. Page actualités   → https://www.steg.com.tn/fr/actualites
 *
 * Le contenu est mis en cache 2h (TTL configurable via STEG_CACHE_TTL dans .env).
 */
class StegRssService
{
    /** @var int Durée du cache en secondes (défaut : 2h) */
    protected int $cacheTtl;

    /** @var string URL du flux RSS STEG */
    protected string $rssUrl = 'https://www.steg.com.tn/fr/rss.xml';

    /** @var string URL de la page interruptions STEG */
    protected string $interruptionsUrl = 'https://www.steg.com.tn/fr/interruptions-de-service';

    /** @var string URL de la page actualités STEG */
    protected string $actualitesUrl = 'https://www.steg.com.tn/fr/actualites';

    /**
     * Dictionnaire de correspondance gouvernorat/ville → mots-clés tunisiens.
     * Permet de détecter la zone concernée dans le texte STEG.
     */
    protected array $zoneKeywords = [
        'tunis'      => ['tunis', 'carthage', 'la marsa', 'bab bhar', 'bab saadoun', 'ettahrir'],
        'ariana'     => ['ariana', 'soukra', 'raoued', 'mnihla', 'kalaat'],
        'ben arous'  => ['ben arous', 'rades', 'ezzahra', 'bou mhel', 'megrine', 'fouchana'],
        'manouba'    => ['manouba', 'denden', 'tebourba', 'jedaida'],
        'nabeul'     => ['nabeul', 'hammamet', 'kelibia', 'grombalia', 'menzel temime'],
        'zaghouan'   => ['zaghouan', 'zriba'],
        'bizerte'    => ['bizerte', 'mateur', 'menzel bourguiba', 'sejnane', 'utique'],
        'beja'       => ['béja', 'beja', 'medjez el bab', 'mejez el bab', 'nefza', 'testour', 'thibar'],
        'jendouba'   => ['jendouba', 'ain draham', 'tabarka', 'fernana', 'bou salem'],
        'kef'        => ['kef', 'le kef', 'el kef', 'sakiet', 'tajerouine', 'nebeur'],
        'siliana'    => ['siliana', 'maktar', 'rohia', 'bou arada'],
        'kairouan'   => ['kairouan', 'haffouz', 'oueslatia', 'sbikha', 'nasrallah'],
        'kasserine'  => ['kasserine', 'sbeitla', 'feriana', 'thala', 'foussana'],
        'sidi bouzid'=> ['sidi bouzid', 'meknassy', 'jelma', 'regueb'],
        'sousse'     => ['sousse', 'msaken', 'enfidha', 'kalaa kebira', 'akouda'],
        'monastir'   => ['monastir', 'moknine', 'ksar hellal', 'bembla', 'sahline'],
        'mahdia'     => ['mahdia', 'ksour essaf', 'chebba', 'bou merdes'],
        'sfax'       => ['sfax', 'agareb', 'jebeniana', 'mahres', 'sakiet eddaier'],
        'gafsa'      => ['gafsa', 'metlaoui', 'redeyef', 'moulares', 'el guettar'],
        'tozeur'     => ['tozeur', 'nefta', 'degache'],
        'kebili'     => ['kebili', 'douz', 'souk lahad'],
        'gabes'      => ['gabes', 'mareth', 'matmata', 'metouia', 'el hamma'],
        'medenine'   => ['medenine', 'zarzis', 'jerba', 'djerba', 'houmet souk', 'ben gardane'],
        'tataouine'  => ['tataouine', 'remada', 'dhehiba', 'bir lahmar'],
    ];

    public function __construct()
    {
        $this->cacheTtl = (int) config('services.steg.cache_ttl', 7200); // 2h
    }

    // ══════════════════════════════════════════════════════════════════
    // ── API PUBLIQUE ──────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════

    /**
     * Point d'entrée principal : récupère toutes les coupures STEG détectées.
     *
     * @return array{items: array, source: string, fetched_at: string, from_cache: bool}
     */
    public function fetchCoupures(): array
    {
        $cacheKey = 'steg_coupures_v2';

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $cached['from_cache'] = true;
            return $cached;
        }

        $result = $this->doParsing();
        Cache::put($cacheKey, $result, $this->cacheTtl);

        return $result;
    }

    /**
     * Synchronise les coupures STEG détectées avec la base de données HeatAlert.
     * Crée uniquement les nouvelles coupures (déduplique par steg_ref).
     *
     * @return array{created: int, skipped: int, matched_zones: string[], errors: string[]}
     */
    public function syncToDatabase(): array
    {
        $data    = $this->fetchCoupures();
        $created = 0;
        $skipped = 0;
        $matched = [];
        $errors  = [];

        foreach ($data['items'] as $item) {
            try {
                // Vérifier si déjà importé (évite les doublons)
                if (!empty($item['steg_ref']) && Coupure::where('steg_ref', $item['steg_ref'])->exists()) {
                    $skipped++;
                    continue;
                }

                // Trouver la zone correspondante
                $zone = $this->resolveZone($item['zones_detectees'] ?? []);
                if (!$zone) {
                    $errors[] = "Aucune zone trouvée pour : " . ($item['titre'] ?? '?');
                    continue;
                }

                // Créer la coupure officielle STEG
                Coupure::create([
                    'type'       => $item['type'] ?? 'delestage',
                    'statut'     => $this->determineStatut($item),
                    'date_debut' => $item['date_debut'] ?? now(),
                    'date_fin'   => $item['date_fin'] ?? null,
                    'cause'      => $this->buildCauseLabel($item),
                    'zone_id'    => $zone->id,
                    'steg_ref'   => $item['steg_ref'] ?? null,
                ]);

                $created++;
                $matched[] = $zone->nom ?? $zone->ville;

            } catch (\Throwable $e) {
                Log::error('[StegRssService] Erreur sync coupure : ' . $e->getMessage());
                $errors[] = $e->getMessage();
            }
        }

        // Invalider le cache après sync pour forcer un rafraîchissement
        Cache::forget('steg_coupures_v2');
        Cache::forget('chatbot_knowledge_*');

        return compact('created', 'skipped', 'matched', 'errors');
    }

    /**
     * Vide le cache STEG manuellement.
     */
    public function clearCache(): void
    {
        Cache::forget('steg_coupures_v2');
    }

    // ══════════════════════════════════════════════════════════════════
    // ── PARSING ──────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════

    /**
     * Exécute le parsing multi-source et retourne les items normalisés.
     */
    protected function doParsing(): array
    {
        $items  = [];
        $source = 'none';

        // Source 1 : RSS STEG (general news, on filtre les coupures)
        $rssItems = $this->parseRss();
        if (!empty($rssItems)) {
            $items  = array_merge($items, $rssItems);
            $source = 'rss';
        }

        // Source 2 : Page interruptions (scraping HTML)
        $htmlItems = $this->scrapeInterruptions();
        if (!empty($htmlItems)) {
            $items  = array_merge($items, $htmlItems);
            $source = $source === 'rss' ? 'rss+html' : 'html';
        }

        // Source 3 : Page actualités (scraping, fallback)
        if (empty($items)) {
            $items  = $this->scrapeActualites();
            $source = 'actualites';
        }

        // Si aucune donnée réelle trouvée → données de démonstration
        if (empty($items)) {
            $items  = $this->getDemoData();
            $source = 'demo';
        }

        return [
            'items'      => $items,
            'source'     => $source,
            'fetched_at' => now()->format('d/m/Y H:i'),
            'from_cache' => false,
        ];
    }

    /**
     * Parse le flux RSS STEG et filtre les entrées liées aux coupures.
     */
    protected function parseRss(): array
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'User-Agent' => 'HeatAlert/1.0 (heatalert.tn)',
                'Accept'     => 'application/rss+xml, application/xml, text/xml',
            ])->get($this->rssUrl);

            if (!$response->successful()) {
                return [];
            }

            $xml   = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
            $items = [];

            if (!$xml || !isset($xml->channel->item)) {
                return [];
            }

            foreach ($xml->channel->item as $item) {
                $titre       = html_entity_decode((string) $item->title, ENT_QUOTES, 'UTF-8');
                $description = strip_tags(html_entity_decode((string) $item->description, ENT_QUOTES, 'UTF-8'));
                $link        = (string) $item->link;
                $pubDate     = (string) $item->pubDate;
                $guid        = (string) $item->guid;

                $fullText = mb_strtolower($titre . ' ' . $description);

                // Filtrer uniquement les articles liés aux coupures/interruptions
                if (!$this->isCoupureRelated($fullText)) {
                    continue;
                }

                $zones = $this->detectZones($fullText);
                $dates = $this->extractDates($fullText, $pubDate);

                $items[] = [
                    'steg_ref'        => 'RSS-' . md5($guid ?: $link),
                    'titre'           => $titre,
                    'description'     => mb_substr($description, 0, 500),
                    'url'             => $link,
                    'published_at'    => $pubDate ? Carbon::parse($pubDate)->format('d/m/Y H:i') : null,
                    'type'            => $this->detectType($fullText),
                    'zones_detectees' => $zones,
                    'date_debut'      => $dates['debut'],
                    'date_fin'        => $dates['fin'],
                    'source'          => 'rss',
                    'confidence'      => $this->computeConfidence($zones, $dates),
                ];
            }

            return $items;

        } catch (\Throwable $e) {
            Log::warning('[StegRssService] Erreur RSS : ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Scrape la page interruptions STEG.
     */
    protected function scrapeInterruptions(): array
    {
        return $this->scrapePage($this->interruptionsUrl, 'interruptions');
    }

    /**
     * Scrape la page actualités STEG.
     */
    protected function scrapeActualites(): array
    {
        return $this->scrapePage($this->actualitesUrl, 'actualites');
    }

    /**
     * Scrape une page STEG et extrait les informations de coupures.
     */
    protected function scrapePage(string $url, string $sourceLabel): array
    {
        try {
            $response = Http::timeout(15)->withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (compatible; HeatAlert/1.0; +https://heatalert.tn)',
                'Accept'          => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'fr-TN,fr;q=0.9',
            ])->get($url);

            if (!$response->successful()) {
                return [];
            }

            $html  = $response->body();
            $items = [];

            // Extraire les blocs de contenu pertinents avec regex
            // Patterns communs des sites Drupal STEG
            $patterns = [
                // Blocs d'articles/news
                '/<article[^>]*>(.*?)<\/article>/si',
                // Blocs de type "views-row"
                '/<div[^>]*class="[^"]*views-row[^"]*"[^>]*>(.*?)<\/div>\s*(?=<div[^>]*class="[^"]*views-row)/si',
                // Blocs field-items (Drupal)
                '/<div[^>]*class="[^"]*field-item[^"]*"[^>]*>(.*?)<\/div>/si',
            ];

            $extractedBlocks = [];
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $html, $matches)) {
                    $extractedBlocks = array_merge($extractedBlocks, $matches[1]);
                }
            }

            foreach ($extractedBlocks as $block) {
                $text = strip_tags(html_entity_decode($block, ENT_QUOTES, 'UTF-8'));
                $text = preg_replace('/\s+/', ' ', trim($text));

                if (strlen($text) < 30) {
                    continue;
                }

                $textLower = mb_strtolower($text);

                if (!$this->isCoupureRelated($textLower)) {
                    continue;
                }

                $zones = $this->detectZones($textLower);
                $dates = $this->extractDates($textLower, null);

                $items[] = [
                    'steg_ref'        => 'HTML-' . md5($url . $text),
                    'titre'           => mb_substr($text, 0, 100),
                    'description'     => mb_substr($text, 0, 400),
                    'url'             => $url,
                    'published_at'    => now()->format('d/m/Y H:i'),
                    'type'            => $this->detectType($textLower),
                    'zones_detectees' => $zones,
                    'date_debut'      => $dates['debut'],
                    'date_fin'        => $dates['fin'],
                    'source'          => $sourceLabel,
                    'confidence'      => $this->computeConfidence($zones, $dates),
                ];
            }

            return $items;

        } catch (\Throwable $e) {
            Log::warning("[StegRssService] Erreur scraping {$url} : " . $e->getMessage());
            return [];
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // ── DONNÉES DÉMO (quand le site STEG n'est pas accessible) ───────
    // ══════════════════════════════════════════════════════════════════

    /**
     * Retourne des données de démonstration réalistes simulant des
     * annonces STEG typiques, pour permettre de tester l'intégration
     * même quand le site officiel est inaccessible.
     */
    protected function getDemoData(): array
    {
        return [
            [
                'steg_ref'        => 'DEMO-' . date('Ymd') . '-001',
                'titre'           => 'Interruption de service programmée — Béja Nord',
                'description'     => 'La STEG informe sa clientèle de la délégation de Béja Nord qu\'une interruption de service électrique est prévue le ' . now()->addDay()->format('d/m/Y') . ' de 08h00 à 16h00 pour travaux de maintenance sur le réseau haute tension. Prière de prendre les dispositions nécessaires.',
                'url'             => 'https://www.steg.com.tn/fr/interruptions-de-service',
                'published_at'    => now()->format('d/m/Y H:i'),
                'type'            => 'delestage',
                'zones_detectees' => ['béja', 'beja'],
                'date_debut'      => now()->addDay()->setTime(8, 0),
                'date_fin'        => now()->addDay()->setTime(16, 0),
                'source'          => 'demo',
                'confidence'      => 95,
            ],
            [
                'steg_ref'        => 'DEMO-' . date('Ymd') . '-002',
                'titre'           => 'Délestage programmé — Sousse Centre',
                'description'     => 'Suite aux travaux d\'extension du réseau, un délestage est programmé dans le secteur de Sousse Centre le ' . now()->addDays(2)->format('d/m/Y') . ' de 09h00 à 13h00. La STEG s\'excuse pour la gêne occasionnée.',
                'url'             => 'https://www.steg.com.tn/fr/interruptions-de-service',
                'published_at'    => now()->subHours(3)->format('d/m/Y H:i'),
                'type'            => 'delestage',
                'zones_detectees' => ['sousse'],
                'date_debut'      => now()->addDays(2)->setTime(9, 0),
                'date_fin'        => now()->addDays(2)->setTime(13, 0),
                'source'          => 'demo',
                'confidence'      => 90,
            ],
            [
                'steg_ref'        => 'DEMO-' . date('Ymd') . '-003',
                'titre'           => 'Coupure réseau Sfax — Remplacement équipement',
                'description'     => 'Remplacement d\'un transformateur défectueux dans la délégation de Sfax Ville. Interruption de 4 heures prévue ' . now()->format('d/m/Y') . ' à partir de 14h00.',
                'url'             => 'https://www.steg.com.tn/fr/interruptions-de-service',
                'published_at'    => now()->subHour()->format('d/m/Y H:i'),
                'type'            => 'panne',
                'zones_detectees' => ['sfax'],
                'date_debut'      => now()->setTime(14, 0),
                'date_fin'        => now()->setTime(18, 0),
                'source'          => 'demo',
                'confidence'      => 85,
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // ── UTILITAIRES ───────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════

    /**
     * Détermine si un texte est lié à une coupure électrique.
     */
    protected function isCoupureRelated(string $text): bool
    {
        $keywords = [
            'coupure', 'interruption', 'délestage', 'delestage', 'panne',
            'coupures d\'électricité', 'service électrique', 'réseau électrique',
            'alimentation en électricité', 'rétablissement', 'rétablissement du courant',
            'travaux sur le réseau', 'maintenance réseau', 'haute tension',
            'transformateur', 'sans courant', 'sans électricité',
            'انقطاع', 'تيار كهربائي',
        ];

        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détecte les gouvernorats/villes tunisiennes mentionnés dans le texte.
     *
     * @return string[]
     */
    protected function detectZones(string $text): array
    {
        $found = [];

        foreach ($this->zoneKeywords as $gouvernorat => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains(mb_strtolower($text), mb_strtolower($kw))) {
                    $found[] = $gouvernorat;
                    break;
                }
            }
        }

        return array_unique($found);
    }

    /**
     * Extrait les dates de début/fin d'une coupure depuis le texte.
     *
     * @return array{debut: ?Carbon, fin: ?Carbon}
     */
    protected function extractDates(string $text, ?string $pubDate): array
    {
        $debut = null;
        $fin   = null;

        // Pattern : "le JJ/MM/AAAA de HH:MM à HH:MM" ou "le JJ/MM de HH h à HH h"
        if (preg_match('/le\s+(\d{1,2}[\/\-]\d{1,2}(?:[\/\-]\d{2,4})?)\s+de\s+(\d{1,2}[h:]\d{0,2})\s+[àa]\s+(\d{1,2}[h:]\d{0,2})/iu', $text, $m)) {
            try {
                $dateStr = $this->normalizeDate($m[1]);
                $debut   = Carbon::parse($dateStr . ' ' . $this->normalizeTime($m[2]));
                $fin     = Carbon::parse($dateStr . ' ' . $this->normalizeTime($m[3]));
                if ($fin->lt($debut)) {
                    $fin->addDay();
                }
            } catch (\Throwable) {}
        }

        // Pattern : "de HH:MM à HH:MM" (sans date explicite → date publication ou demain)
        if (!$debut && preg_match('/de\s+(\d{1,2}[h:]\d{0,2})\s+[àa]\s+(\d{1,2}[h:]\d{0,2})/iu', $text, $m)) {
            try {
                $base  = $pubDate ? Carbon::parse($pubDate) : now()->addDay();
                $debut = $base->copy()->setTimeFromTimeString($this->normalizeTime($m[1]));
                $fin   = $base->copy()->setTimeFromTimeString($this->normalizeTime($m[2]));
                if ($fin->lt($debut)) {
                    $fin->addDay();
                }
            } catch (\Throwable) {}
        }

        // Fallback : date de publication → début immédiat, fin +4h
        if (!$debut && $pubDate) {
            try {
                $debut = Carbon::parse($pubDate);
                $fin   = $debut->copy()->addHours(4);
            } catch (\Throwable) {}
        }

        return ['debut' => $debut, 'fin' => $fin];
    }

    /**
     * Détecte le type de coupure (delestage, surcharge, panne).
     */
    protected function detectType(string $text): string
    {
        if (preg_match('/délestage|delestage/iu', $text)) {
            return 'delestage';
        }
        if (preg_match('/surcharge/iu', $text)) {
            return 'surcharge';
        }
        return 'panne';
    }

    /**
     * Calcule un score de confiance (0-100) basé sur la qualité des données.
     */
    protected function computeConfidence(array $zones, array $dates): int
    {
        $score = 50;

        if (!empty($zones)) {
            $score += min(30, count($zones) * 15);
        }
        if ($dates['debut'] !== null) {
            $score += 15;
        }
        if ($dates['fin'] !== null) {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Résout la zone HeatAlert depuis les mots-clés STEG détectés.
     */
    protected function resolveZone(array $zonesDetectees): ?Zone
    {
        foreach ($zonesDetectees as $keyword) {
            $zone = Zone::where('actif', true)
                ->where(function ($q) use ($keyword) {
                    $q->where('nom', 'like', "%{$keyword}%")
                      ->orWhere('ville', 'like', "%{$keyword}%")
                      ->orWhere('gouvernorat', 'like', "%{$keyword}%");
                })->first();

            if ($zone) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * Détermine le statut d'une coupure selon ses dates.
     */
    protected function determineStatut(array $item): string
    {
        $debut = $item['date_debut'] ?? null;
        $fin   = $item['date_fin'] ?? null;

        if (!$debut) {
            return 'prevue';
        }

        $now = now();

        if ($fin && $now->gt($fin)) {
            return 'terminee';
        }
        if ($now->gte($debut)) {
            return 'en_cours';
        }

        return 'prevue';
    }

    /**
     * Construit le libellé de cause incluant la source STEG officielle.
     */
    protected function buildCauseLabel(array $item): string
    {
        $base = $item['description'] ?? $item['titre'] ?? 'Coupure programmée STEG';

        return '[STEG Officiel] ' . mb_substr($base, 0, 300);
    }

    /**
     * Normalise une chaîne de date partielle (JJ/MM ou JJ/MM/AAAA).
     */
    protected function normalizeDate(string $raw): string
    {
        $raw = str_replace('-', '/', $raw);
        $parts = explode('/', $raw);

        if (count($parts) === 2) {
            $parts[] = date('Y');
        }

        if (strlen($parts[2]) === 2) {
            $parts[2] = '20' . $parts[2];
        }

        return $parts[2] . '-' . str_pad($parts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT);
    }

    /**
     * Normalise une chaîne d'heure (8h, 8h30, 08:30 → 08:30:00).
     */
    protected function normalizeTime(string $raw): string
    {
        $raw = str_replace('h', ':', $raw);
        $parts = explode(':', $raw);

        $h = str_pad($parts[0] ?? '0', 2, '0', STR_PAD_LEFT);
        $m = str_pad($parts[1] ?? '0', 2, '0', STR_PAD_LEFT);

        return "{$h}:{$m}:00";
    }
}

