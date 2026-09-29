<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FraisSupplementaireRequest;
use App\Models\FraisSupplementaire;
use App\Models\SalleDeClasse;
use App\Models\Section;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class FraisSupplementaireController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const PER_PAGE     = 20;
    private const PER_PAGE_MAX = 100;
    private const PER_PAGE_MIN = 5;

    private const SALLES_CACHE_KEY   = 'salles:liste:v3';
    private const SECTIONS_CACHE_KEY = 'sections:liste:v1';
    private const TAUX_CACHE_KEY     = 'taux_change';
    private const CACHE_TTL          = 3600;

    private const REDIRECT_ROUTE = 'admin.frais-supplementaires.index';

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Request $request): View
    {
        /* ---------- Validation ---------- */
        $validated = $request->validate([
            'search'     => ['nullable', 'string', 'max:100'],
            'est_ouvert' => ['nullable', 'in:0,1'],
            'per_page'   => [
                'nullable',
                'integer',
                'min:' . self::PER_PAGE_MIN,
                'max:' . self::PER_PAGE_MAX,
            ],
        ]);

        $search    = trim((string) ($validated['search'] ?? ''));
        $estOuvert = $validated['est_ouvert'] ?? null;
        $perPage   = (int) ($validated['per_page'] ?? self::PER_PAGE);

        /* ---------- Liste paginée ---------- */
        $frais = FraisSupplementaire::query()
            ->with(['salles:id,nom'])
            ->withCount('paiements')
            ->recherche($search)
            ->when($estOuvert === '1', fn ($q) => $q->where('est_ouvert', true))
            ->when($estOuvert === '0', fn ($q) => $q->where('est_ouvert', false))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        /* ---------- Statistiques globales (1 requête) ---------- */
        $stats = FraisSupplementaire::query()
            ->selectRaw('
                COUNT(*) AS total,
                SUM(CASE WHEN est_ouvert = 1 THEN 1 ELSE 0 END) AS ouverts,
                SUM(CASE WHEN est_ouvert = 0 THEN 1 ELSE 0 END) AS fermes,
                COALESCE(SUM(montant), 0) AS montant_total
            ')
            ->first();

        $tauxChange = (float) Cache::get(self::TAUX_CACHE_KEY, 2800);

        return view('admin.frais-supplementaires.index', [
            'frais'        => $frais,
            'search'       => $search,
            'estOuvert'    => $estOuvert,
            'totalFrais'   => (int)   $stats->total,
            'totalOuverts' => (int)   $stats->ouverts,
            'totalFermes'  => (int)   $stats->fermes,
            'montantTotal' => (float) $stats->montant_total,
            'tauxChange'   => $tauxChange,
        ]);
    }

    // ============================================================
    // CREATE / STORE
    // ============================================================

    public function create(): View
    {
        return view('admin.frais-supplementaires.create', [
            'salles'     => $this->getSallesCache(),
            'sections'   => $this->getSectionsCache(),
            'tauxChange' => (float) Cache::get(self::TAUX_CACHE_KEY, 2800),
        ]);
    }

    public function store(FraisSupplementaireRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $frais = DB::transaction(function () use ($data): FraisSupplementaire {
                $frais = FraisSupplementaire::create([
                    'libelle'                => $data['libelle'],
                    'montant'                => (float) $data['montant'],
                    'date_debut'             => $data['date_debut'],
                    'date_fin'               => $data['date_fin'],
                    'description'            => $data['description'] ?? null,
                    'est_ouvert'             => (bool) ($data['est_ouvert'] ?? true),
                    'est_pour_toutes_salles' => (bool) ($data['est_pour_toutes_salles'] ?? true),
                ]);

                if (!$frais->est_pour_toutes_salles && !empty($data['salles'])) {
                    $frais->salles()->sync($data['salles']);
                }

                return $frais;
            });

            $this->logAction($request, 'info', 'Frais supplémentaire créé', [
                'frais_id' => $frais->id,
                'libelle'  => $frais->libelle,
            ]);

            return $this->redirectSuccess("Frais « {$frais->libelle} » créé avec succès.");

        } catch (Throwable $e) {
            return $this->handleException($e, 'création', $request, $data);
        }
    }

    // ============================================================
    // SHOW
    // ============================================================

    public function show(FraisSupplementaire $fraisSupplementaire): View
    {
        $fraisSupplementaire
            ->load(['salles:id,nom'])
            ->loadCount('paiements');

        $paiements = $fraisSupplementaire->paiements()
            ->with(['eleve:id,nom,postnom,prenom'])
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, ['*'], 'page_paiements')
            ->withQueryString();

        return view('admin.frais-supplementaires.show', [
            'fraisSupplementaire' => $fraisSupplementaire,
            'paiements'           => $paiements,
            'tauxChange'          => (float) Cache::get(self::TAUX_CACHE_KEY, 2800),
        ]);
    }

    // ============================================================
    // EDIT / UPDATE
    // ============================================================

    public function edit(FraisSupplementaire $fraisSupplementaire): View
    {
        return view('admin.frais-supplementaires.edit', [
            'fraisSupplementaire' => $fraisSupplementaire,
            'salles'              => $this->getSallesCache(),
            'sections'            => $this->getSectionsCache(),
            'tauxChange'          => (float) Cache::get(self::TAUX_CACHE_KEY, 2800),
            'sallesIds'           => $fraisSupplementaire->salles()
                ->pluck('salles_de_classe.id')
                ->all(),
        ]);
    }

    public function update(
        FraisSupplementaireRequest $request,
        FraisSupplementaire $fraisSupplementaire
    ): RedirectResponse {
        $data = $request->validated();

        try {
            DB::transaction(function () use ($data, $fraisSupplementaire): void {
                $fraisSupplementaire->update([
                    'libelle'                => $data['libelle'],
                    'montant'                => (float) $data['montant'],
                    'date_debut'             => $data['date_debut'],
                    'date_fin'               => $data['date_fin'],
                    'description'            => $data['description'] ?? null,
                    'est_ouvert'             => (bool) ($data['est_ouvert'] ?? false),
                    'est_pour_toutes_salles' => (bool) ($data['est_pour_toutes_salles'] ?? false),
                ]);

                if ($fraisSupplementaire->est_pour_toutes_salles) {
                    $fraisSupplementaire->salles()->detach();
                } else {
                    $fraisSupplementaire->salles()->sync($data['salles'] ?? []);
                }
            });

            $this->logAction($request, 'info', 'Frais supplémentaire mis à jour', [
                'frais_id' => $fraisSupplementaire->id,
            ]);

            return $this->redirectSuccess("Frais « {$fraisSupplementaire->libelle} » mis à jour.");

        } catch (Throwable $e) {
            return $this->handleException($e, 'mise à jour', $request, $data, $fraisSupplementaire);
        }
    }

    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(FraisSupplementaire $fraisSupplementaire): RedirectResponse
    {
        $paiementsCount = $fraisSupplementaire->paiements()->count();

        if ($paiementsCount > 0) {
            $plural = $paiementsCount > 1 ? 's' : '';

            return back()->with('error', sprintf(
                'Impossible de supprimer : ce frais a %d paiement%s associé%s.',
                $paiementsCount,
                $plural,
                $plural
            ));
        }

        $libelle = $fraisSupplementaire->libelle;

        try {
            DB::transaction(function () use ($fraisSupplementaire): void {
                $fraisSupplementaire->salles()->detach();
                $fraisSupplementaire->delete();
            });

            $this->logAction(request(), 'info', 'Frais supplémentaire supprimé', [
                'libelle' => $libelle,
            ]);

            return $this->redirectSuccess("Frais « {$libelle} » supprimé avec succès.");

        } catch (Throwable $e) {
            return $this->handleException($e, 'suppression', request());
        }
    }

    // ============================================================
    // TOGGLE OUVERTURE
    // ============================================================

    public function toggleOuverture(FraisSupplementaire $fraisSupplementaire): RedirectResponse
    {
        try {
            $nouvelEtat = DB::transaction(function () use ($fraisSupplementaire): bool {
                $fresh = FraisSupplementaire::whereKey($fraisSupplementaire->getKey())
                    ->lockForUpdate()
                    ->first();

                if (!$fresh) {
                    throw new \RuntimeException('Frais introuvable.');
                }

                $fresh->est_ouvert = !$fresh->est_ouvert;
                $fresh->save();

                return (bool) $fresh->est_ouvert;
            });

            $etat = $nouvelEtat ? 'ouvert' : 'fermé';

            $this->logAction(request(), 'info', "Frais supplémentaire {$etat}", [
                'frais_id' => $fraisSupplementaire->id,
            ]);

            return back()->with(
                'success',
                "Frais « {$fraisSupplementaire->libelle} » maintenant {$etat}."
            );

        } catch (Throwable $e) {
            Log::error('Erreur toggle frais supplémentaire', [
                'frais_id' => $fraisSupplementaire->id,
                'error'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible de changer l\'état du frais.');
        }
    }

    // ============================================================
    // HELPERS PRIVÉS — CACHE
    // ============================================================

    /**
     * Liste des salles (cache auto-réparateur).
     * Cache un tableau → évite `__PHP_Incomplete_Class`.
     */
    private function getSallesCache(): Collection
    {
        $cached = Cache::get(self::SALLES_CACHE_KEY);

        if (!is_array($cached)) {
            Cache::forget(self::SALLES_CACHE_KEY);
            $cached = null;
        }

        if ($cached === null) {
            $cached = SalleDeClasse::query()
                ->orderBy('nom')
                ->get(['id', 'nom', 'section_id'])
                ->toArray();

            Cache::put(self::SALLES_CACHE_KEY, $cached, self::CACHE_TTL);
        }

        return SalleDeClasse::hydrate($cached);
    }

    /**
     * Liste des sections (cache auto-réparateur).
     */
    private function getSectionsCache(): Collection
    {
        $cached = Cache::get(self::SECTIONS_CACHE_KEY);

        if (!is_array($cached)) {
            Cache::forget(self::SECTIONS_CACHE_KEY);
            $cached = null;
        }

        if ($cached === null) {
            $cached = Section::query()
                ->orderBy('nom')
                ->get(['id', 'nom'])
                ->toArray();

            Cache::put(self::SECTIONS_CACHE_KEY, $cached, self::CACHE_TTL);
        }

        return Section::hydrate($cached);
    }

    /**
     * Invalide les caches salles + sections.
     * À appeler depuis SalleDeClasseController / SectionController.
     */
    public static function invaliderCacheSalles(): void
    {
        Cache::forget(self::SALLES_CACHE_KEY);
        Cache::forget(self::SECTIONS_CACHE_KEY);
    }

    // ============================================================
    // HELPERS PRIVÉS — LOGS & REDIRECTIONS
    // ============================================================

    private function logAction(Request $request, string $level, string $message, array $context = []): void
    {
        Log::{$level}($message, array_merge($context, [
            'admin_id' => $request->user()?->id,
        ]));
    }

    private function redirectSuccess(string $message): RedirectResponse
    {
        return redirect()
            ->route(self::REDIRECT_ROUTE)
            ->with('success', $message);
    }

    private function handleException(
        Throwable $e,
        string $operation,
        Request $request,
        array $data = [],
        ?FraisSupplementaire $frais = null
    ): RedirectResponse {
        Log::error("Erreur {$operation} frais supplémentaire", array_filter([
            'frais_id' => $frais?->getKey(),
            'message'  => $e->getMessage(),
            'class'    => get_class($e),
            'data'     => $data ?: null,
            'input'    => $request->except(['_token', '_method']),
            'trace'    => $e->getTraceAsString(),
        ]));

        $message = config('app.debug')
            ? "Erreur {$operation} : {$e->getMessage()}"
            : "Une erreur est survenue lors de la {$operation}.";

        return back()
            ->withInput()
            ->with('error', $message);
    }
}