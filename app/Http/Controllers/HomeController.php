<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\AnneeScolaire;
use App\Models\CarouselImage;
use App\Models\Devise;
use App\Models\Inscription;
use App\Models\Option;
use App\Models\PeriodeNote;
use App\Models\ReglementInterieur;
use App\Models\SalleDeClasse;
use App\Models\Section;
use App\Models\Session;
use App\Models\SiteSetting;
use App\Services\NoteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class HomeController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const ANNONCE_PUBLIC = 'public';
    private const ANNONCE_PRIVE  = 'prive';

    /** Version du schéma de cache — incrémenter à chaque changement de structure. */
    private const CACHE_VERSION = 'v5';   // ⬆️ v5 (était v4) → ajout des règlements

    /** Durées de cache (en secondes). */
    private const HOME_CACHE_TTL         = 600;   // 10 min
    private const TAUX_CACHE_TTL         = 3600;  // 1 h
    private const CLASSEMENT_CACHE_TTL   = 300;   // 5 min
    private const INSCRIPTIONS_CACHE_TTL = 300;   // 5 min
    private const META_CACHE_TTL         = 600;   // 10 min

    /** Taux de change par défaut (fallback). */
    private const TAUX_CHANGE_DEFAUT = 2800.0;

    /** Préfixe global pour regrouper les clés du cache home. */
    private const CACHE_PREFIX = 'home_';

    /** Tag unique pour l'invalidation groupée (Redis / Memcached). */
    private const CACHE_TAG = self::CACHE_PREFIX . 'tag';

    /** Clés de cache (versionnées). */
    public const CACHE_KEY_HOME                = self::CACHE_PREFIX . 'page_data_'           . self::CACHE_VERSION;
    public const CACHE_KEY_HOME_GUEST          = self::CACHE_PREFIX . 'page_data_guest_'     . self::CACHE_VERSION;
    public const CACHE_KEY_HOME_USER           = self::CACHE_PREFIX . 'page_data_user_';
    public const CACHE_KEY_TAUX                = self::CACHE_PREFIX . 'taux_usd_cdf_'        . self::CACHE_VERSION;
    public const CACHE_KEY_CLASSEMENT_META     = self::CACHE_PREFIX . 'classement_meta_'     . self::CACHE_VERSION;
    public const CACHE_KEY_INSCRIPTIONS_SALLES = self::CACHE_PREFIX . 'inscriptions_salles_' . self::CACHE_VERSION;

    /** Préfixes pour les clés dynamiques. */
    private const CACHE_PREFIX_CLASSEMENT   = self::CACHE_PREFIX . 'classement_'   . self::CACHE_VERSION . '_';
    private const CACHE_PREFIX_INSCRIPTIONS = self::CACHE_PREFIX . 'inscriptions_' . self::CACHE_VERSION . '_';

    /**
     * Champs de type date à réhydrater en Carbon après passage par le cache.
     */
    private const DATE_FIELDS = [
        'date_debut', 'date_fin', 'created_at', 'updated_at', 'deleted_at',
        'date_inscription', 'date_naissance', 'lu_a',
    ];

    /** Images de secours pour le carrousel. */
    private const DEFAULT_CAROUSEL_IMAGES = [
        [
            'image_url' => 'https://images.pexels.com/photos/8613089/pexels-photo-8613089.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'alt_text'  => "Élèves en train d'étudier",
        ],
        [
            'image_url' => 'https://images.pexels.com/photos/8471782/pexels-photo-8471782.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'alt_text'  => 'Classe moderne',
        ],
        [
            'image_url' => 'https://images.pexels.com/photos/5905709/pexels-photo-5905709.jpeg?auto=compress&cs=tinysrgb&w=1200',
            'alt_text'  => 'Élèves en cours',
        ],
    ];

    public function __construct(
        protected NoteService $noteService,
    ) {}

    // ============================================================
    // PAGE D'ACCUEIL
    // ============================================================

    public function index(): View
    {
        $user = auth()->user();

        // ---------- Données statiques (cachées globalement) ----------
        $staticData = $this->rememberCached(self::CACHE_KEY_HOME, function (): array {
            $sallesDeClasse = SalleDeClasse::with('section:id,nom')
                ->orderBy('nom')
                ->get();

            return [
                'settings'         => SiteSetting::getSettingsRaw(),
                'anneesScolaires'  => AnneeScolaire::ouvertes()
                                        ->orderByDesc('date_debut')
                                        ->get()
                                        ->toArray(),
                'sessions'         => Session::orderBy('nom')->get()->toArray(),
                'sections'         => Section::orderBy('nom')->get()->toArray(),
                'options'          => Option::orderBy('nom')->get()->toArray(),
                'sallesParSection' => $sallesDeClasse
                                        ->groupBy(fn ($s) => $s->section?->nom ?? 'Sans section')
                                        ->map(fn (Collection $group) => $group->toArray())
                                        ->toArray(),
                'carouselImages'   => $this->getCarouselImages()->all(),
                'tauxChange'       => $this->getTauxChange(),
                // ✅ NOUVEAU : règlement intérieur groupé par catégorie
                'reglements'       => $this->getReglementsGrouped(),
            ];
        });

        $staticData = $this->hydrateStaticData($staticData);

        // ---------- Données dynamiques (par utilisateur) ----------
        $userData = $user
            ? $this->getUserData($user)
            : $this->getGuestData();

        return view('welcome', array_merge($staticData, $userData));
    }

    private function getUserData(\App\Models\User $user): array
    {
        $cacheKey = self::CACHE_KEY_HOME_USER . $user->id;

        $data = $this->rememberCached($cacheKey, function () use ($user): array {
            return [
                'annonces'        => $this->getAnnoncesVisibles($user)
                                          ->map->toArray()
                                          ->toArray(),
                'annoncesNonLues' => $this->getAnnoncesNonLues($user),
            ];
        });

        return [
            'annonces'        => $this->toObjectCollection($data['annonces']),
            'annoncesNonLues' => (int) $data['annoncesNonLues'],
        ];
    }

    private function getGuestData(): array
    {
        $data = $this->rememberCached(self::CACHE_KEY_HOME_GUEST, function (): array {
            return [
                'annonces' => $this->getAnnoncesVisibles(null)
                                   ->map->toArray()
                                   ->toArray(),
            ];
        });

        return [
            'annonces'        => $this->toObjectCollection($data['annonces']),
            'annoncesNonLues' => 0,
        ];
    }

    /**
     * Transforme les arrays plats en objets exploitables par la vue.
     */
    private function hydrateStaticData(array $data): array
    {
        // ---- Settings : objet + Carbon pour creation_date ----
        $settings = (object) $data['settings'];
        if (!empty($settings->creation_date) && is_string($settings->creation_date)) {
            try {
                $settings->creation_date = Carbon::parse($settings->creation_date);
            } catch (\Throwable) {
                $settings->creation_date = null;
            }
        }

        return [
            'settings'         => $settings,
            'anneesScolaires'  => $this->toObjectCollection($data['anneesScolaires']),
            'sessions'         => $this->toObjectCollection($data['sessions']),
            'sections'         => $this->toObjectCollection($data['sections']),
            'options'          => $this->toObjectCollection($data['options']),
            'sallesParSection' => collect($data['sallesParSection'])
                                    ->map(fn ($salles) => $this->toObjectCollection($salles)),
            'carouselImages'   => $this->toObjectCollection($data['carouselImages']),
            'tauxChange'       => (float) $data['tauxChange'],
            // ✅ NOUVEAU : règlements réhydratés en objets
            'reglements'       => [
                'regles'        => $this->toObjectCollection($data['reglements']['regles']        ?? []),
                'obligations'   => $this->toObjectCollection($data['reglements']['obligations']   ?? []),
                'interdictions' => $this->toObjectCollection($data['reglements']['interdictions'] ?? []),
            ],
        ];
    }

    // ============================================================
    // GESTION DU CACHE
    // ============================================================

    private function rememberCached(string $key, \Closure $callback): array
    {
        return $this->rememberCachedWithTtl($key, $callback, self::HOME_CACHE_TTL);
    }

    private function rememberCachedWithTtl(string $key, \Closure $callback, int $ttl): array
    {
        if ($this->supportsCacheTags()) {
            return Cache::tags([self::CACHE_TAG])->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    private function supportsCacheTags(): bool
    {
        return in_array(config('cache.default'), ['redis', 'memcached'], true);
    }

    public static function clearHomeCache(): void
    {
        if (in_array(config('cache.default'), ['redis', 'memcached'], true)) {
            try {
                Cache::tags([self::CACHE_TAG])->flush();
                return;
            } catch (\Throwable $e) {
                Log::warning('Flush par tag échoué, fallback clé par clé', ['error' => $e->getMessage()]);
            }
        }

        foreach ([
            self::CACHE_KEY_HOME,
            self::CACHE_KEY_HOME_GUEST,
            self::CACHE_KEY_TAUX,
            self::CACHE_KEY_CLASSEMENT_META,
            self::CACHE_KEY_INSCRIPTIONS_SALLES,
        ] as $key) {
            Cache::forget($key);
        }
    }

    public static function clearUserCache(int $userId): void
    {
        Cache::forget(self::CACHE_KEY_HOME_USER . $userId);
    }

    public static function refreshTauxChange(): void
    {
        Cache::forget(self::CACHE_KEY_TAUX);
        self::clearHomeCache();
    }

    // ============================================================
    // HELPERS DE TRANSFORMATION
    // ============================================================

    private function toObjectCollection(iterable $items): Collection
    {
        return collect($items)->map(fn ($item) => $this->arrayToObjectRecursive((array) $item));
    }

    private function arrayToObjectRecursive(array $array): \stdClass
    {
        $object = json_decode(json_encode($array, JSON_THROW_ON_ERROR), false);

        foreach (self::DATE_FIELDS as $field) {
            if (property_exists($object, $field)
                && !empty($object->{$field})
                && is_string($object->{$field})
            ) {
                try {
                    $object->{$field} = Carbon::parse($object->{$field});
                } catch (\Throwable) {
                    // On laisse la valeur brute
                }
            }
        }

        return $object;
    }

    // ============================================================
    // DONNÉES DYNAMIQUES
    // ============================================================

    private function getAnnoncesVisibles(?\App\Models\User $user): Collection
    {
        return Annonce::query()
            ->active()
            ->when(
                $user !== null,
                fn ($q) => $q->whereIn('type', [self::ANNONCE_PUBLIC, self::ANNONCE_PRIVE]),
                fn ($q) => $q->where('type', self::ANNONCE_PUBLIC),
            )
            ->orderByDesc('date_debut')
            ->get();
    }

    private function getAnnoncesNonLues(\App\Models\User $user): int
    {
        return Annonce::query()
            ->active()
            ->whereDoesntHave('lecteurs', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                      ->whereNotNull('lu_a');
            })
            ->count();
    }

    private function getCarouselImages(): Collection
    {
        $images = CarouselImage::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (CarouselImage $img) => $img->toCarouselArray());

        return $images->isNotEmpty()
            ? $images
            : collect(self::DEFAULT_CAROUSEL_IMAGES);
    }

    private function getTauxChange(): float
    {
        return Cache::remember(self::CACHE_KEY_TAUX, self::TAUX_CACHE_TTL, function (): float {
            try {
                $source = Devise::where('code', 'USD')->first();
                $cible  = Devise::where('code', 'CDF')->first();

                if ($source && $cible) {
                    return (float) $source->tauxVers($cible);
                }

                Log::warning('Taux de change USD/CDF non configuré, utilisation du fallback');

                return self::TAUX_CHANGE_DEFAUT;
            } catch (\Throwable $e) {
                Log::error('Erreur récupération taux de change', ['error' => $e->getMessage()]);

                return self::TAUX_CHANGE_DEFAUT;
            }
        });
    }

    /**
     * Règlements intérieurs actifs, groupés par catégorie.
     * Utilise les scopes du modèle `ReglementInterieur`.
     *
     * @return array{regles: array, obligations: array, interdictions: array}
     */
    private function getReglementsGrouped(): array
    {
        $grouped = ReglementInterieur::query()
            ->actif()
            ->ordonne()
            ->get()
            ->groupBy('categorie');

        return [
            'regles'        => $grouped->get(ReglementInterieur::CATEGORIE_REGLE, collect())
                                        ->map->toArray()->all(),
            'obligations'   => $grouped->get(ReglementInterieur::CATEGORIE_OBLIGATION, collect())
                                        ->map->toArray()->all(),
            'interdictions' => $grouped->get(ReglementInterieur::CATEGORIE_INTERDICTION, collect())
                                        ->map->toArray()->all(),
        ];
    }

    // ============================================================
    // PAGE PUBLIQUE — CLASSEMENT
    // ============================================================

    public function classement(Request $request): View
    {
        $meta = $this->rememberCachedWithTtl(
            self::CACHE_KEY_CLASSEMENT_META,
            function (): array {
                return [
                    'salles'   => SalleDeClasse::with('section:id,nom')
                                    ->orderBy('nom')
                                    ->get()
                                    ->toArray(),
                    'periodes' => PeriodeNote::orderByDesc('date_debut')->get()->toArray(),
                ];
            },
            self::META_CACHE_TTL,
        );

        $salles   = $this->toObjectCollection($meta['salles']);
        $periodes = $this->toObjectCollection($meta['periodes']);

        $salleId   = (int) $request->input('salle_id');
        $periodeId = (int) $request->input('periode_note_id');

        $classement = null;
        $salle      = null;
        $periode    = null;

        if ($salleId && $periodeId) {
            $salle   = $salles->firstWhere('id', $salleId);
            $periode = $periodes->firstWhere('id', $periodeId);

            abort_if(!$salle || !$periode, 404, 'Salle ou période introuvable.');

            $classement = Cache::remember(
                self::CACHE_PREFIX_CLASSEMENT . "{$salleId}_{$periodeId}",
                self::CLASSEMENT_CACHE_TTL,
                fn () => $this->noteService->calculerClassement($salleId, $periodeId),
            );
        }

        return view('public.classement', compact(
            'salles',
            'periodes',
            'salleId',
            'periodeId',
            'classement',
            'salle',
            'periode',
        ));
    }

    // ============================================================
    // PAGE PUBLIQUE — INSCRIPTIONS PAR SALLE
    // ============================================================

    public function inscriptionsParSalle(Request $request): View
    {
        $sallesRaw = $this->rememberCachedWithTtl(
            self::CACHE_KEY_INSCRIPTIONS_SALLES,
            fn (): array => SalleDeClasse::with('section:id,nom')
                ->orderBy('nom')
                ->get()
                ->toArray(),
            self::META_CACHE_TTL,
        );

        $salles = $this->toObjectCollection($sallesRaw);

        $salleId      = (int) $request->input('salle_id');
        $salle        = null;
        $inscriptions = collect();

        if ($salleId) {
            $salle = $salles->firstWhere('id', $salleId);

            abort_if(!$salle, 404, 'Salle introuvable.');

            $raw = Cache::remember(
                self::CACHE_PREFIX_INSCRIPTIONS . "{$salleId}",
                self::INSCRIPTIONS_CACHE_TTL,
                fn () => Inscription::with(['eleve', 'anneeScolaire'])
                    ->where('salle_classe_id', $salleId)
                    ->orderByDesc('date_inscription')
                    ->get()
                    ->map->toArray()
                    ->toArray(),
            );

            $inscriptions = $this->toObjectCollection($raw);
        }

        return view('public.inscriptions', compact(
            'salles',
            'salleId',
            'salle',
            'inscriptions',
        ));
    }
}