<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaiementSalaireRequest;
use App\Http\Requests\UpdatePaiementSalaireRequest;
use App\Models\AvanceSalaire;
use App\Models\MoisScolaire;
use App\Models\PaiementSalaire;
use App\Models\Section;
use App\Models\User;
use App\Services\AvanceService;
use App\Services\ExportService;
use App\Services\PaiementSalaireService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SalairePaiementController extends Controller
{
    public function __construct(
        protected AvanceService $avanceService,
        protected PaiementSalaireService $paiementService,
        protected ExportService $exportService
    ) {}

    // ============================================================
    // INDEX — Liste des paiements
    // ============================================================

    public function index(Request $request): View
    {
        $request->validate([
            'user_id'          => ['nullable', 'integer', 'exists:users,id'],
            'mois_scolaire_id' => ['nullable', 'integer', 'exists:mois_scolaires,id'],
            'section_id'       => ['nullable', 'string'],
            'statut'           => ['nullable', 'in:' . implode(',', PaiementSalaire::STATUTS)],
            'corbeille'        => ['nullable', 'boolean'],
        ]);

        $mois      = MoisScolaire::orderBy('mois')->get(['id', 'mois', 'nom_mois']);
        $users     = User::orderBy('name')->get(['id', 'name', 'email']);
        $sections  = Section::orderBy('nom')->get(['id', 'nom']);
        $corbeille = $request->boolean('corbeille');

        $filteredQuery = $this->getFilteredQuery($request, $corbeille);

        $paiements = (clone $filteredQuery)
            ->orderByDesc('date_paiement')
            ->paginate(20)
            ->withQueryString();

        // Statistiques en 1 seule requête SQL
        $statsRaw = (clone $filteredQuery)
            ->reorder()
            ->selectRaw('
                COALESCE(SUM(montant_paye_usd), 0) AS total_usd,
                COALESCE(SUM(montant_paye_fc), 0)  AS total_fc,
                SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END) AS nb_paye,
                SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END) AS nb_souspaye,
                SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END) AS nb_surpaye
            ', [
                PaiementSalaire::STATUT_PAYE,
                PaiementSalaire::STATUT_SOUSPAYE,
                PaiementSalaire::STATUT_SURPAYE,
            ])
            ->first();

        $stats = [
            'total_usd'   => (float) ($statsRaw->total_usd   ?? 0),
            'total_fc'    => (float) ($statsRaw->total_fc    ?? 0),
            'nb_paye'     => (int)   ($statsRaw->nb_paye     ?? 0),
            'nb_souspaye' => (int)   ($statsRaw->nb_souspaye ?? 0),
            'nb_surpaye'  => (int)   ($statsRaw->nb_surpaye  ?? 0),
        ];

        $statsMois  = $this->getStatsParMois($filteredQuery);
        $tauxChange = $this->avanceService->getTauxChangeSafe();

        return view('admin.paiement-salaires.index', [
            'paiements'    => $paiements,
            'mois'         => $mois,
            'users'        => $users,
            'sections'     => $sections,
            'tauxChange'   => $tauxChange,
            'statsMois'    => $statsMois,
            'corbeille'    => $corbeille,
            'totalPayeUSD' => $stats['total_usd'],
            'totalPayeFC'  => $stats['total_fc'],
            'nbPaye'       => $stats['nb_paye'],
            'nbSousPaye'   => $stats['nb_souspaye'],
            'nbSurPaye'    => $stats['nb_surpaye'],
        ]);
    }

    // ============================================================
    // CREATE / STORE
    // ============================================================

    public function create(): View
    {
        return view('admin.paiement-salaires.create', [
            'usersData'  => $this->getUsersData(),
            'mois'       => MoisScolaire::orderBy('mois')->get(['id', 'mois', 'nom_mois']),
            'tauxChange' => $this->avanceService->getTauxChangeSafe(),
        ]);
    }

    public function store(StorePaiementSalaireRequest $request): RedirectResponse
    {
        $validated  = $request->validated();
        $tauxChange = $this->avanceService->getTauxChange();

        try {
            $this->paiementService->creerPaiements($validated['paiements'], $tauxChange);

            Log::debug('Paiements de salaire créés', [
                'nb_paiements' => count($validated['paiements']),
                'admin_id'     => auth()->id(),
            ]);

            return redirect()
                ->route('admin.paiement-salaires.index')
                ->with('success', count($validated['paiements']) . ' paiement(s) enregistré(s) avec succès.');

        } catch (Throwable $e) {
            Log::error('Erreur création paiements salaire', [
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', "Une erreur est survenue lors de l'enregistrement.");
        }
    }

    // ============================================================
    // SHOW / EDIT / UPDATE / DESTROY
    // ============================================================

    public function show(PaiementSalaire $paiementSalaire): View
    {
        $paiementSalaire->load(['user', 'moisScolaire', 'remboursementsAvances.avance']);

        return view('admin.paiement-salaires.show', [
            'paiementSalaire' => $paiementSalaire,
            'user'            => $paiementSalaire->user,
            'tauxChange'      => $this->avanceService->getTauxChangeSafe(),
        ]);
    }

    public function edit(PaiementSalaire $paiementSalaire): View
    {
        $paiementSalaire->load(['user', 'moisScolaire', 'remboursementsAvances']);

        return view('admin.paiement-salaires.edit', [
            'paiementSalaire' => $paiementSalaire,
            'usersData'       => $this->getUsersData(),
            'mois'            => MoisScolaire::orderBy('mois')->get(['id', 'mois', 'nom_mois']),
            'tauxChange'      => $this->avanceService->getTauxChangeSafe(),
        ]);
    }

    public function update(UpdatePaiementSalaireRequest $request, PaiementSalaire $paiementSalaire): RedirectResponse
    {
        $validated  = $request->validated();
        $tauxChange = $this->avanceService->getTauxChange();

        try {
            $this->paiementService->mettreAJourPaiement($paiementSalaire, $validated, $tauxChange);

            if ($paiementSalaire->fresh()->report_dette_usd > 0) {
                session()->flash('warning', sprintf(
                    'La dette restante de %s $ sera reportée au mois prochain pour %s.',
                    number_format((float) $paiementSalaire->fresh()->report_dette_usd, 0, ',', ' '),
                    $paiementSalaire->user?->name ?? 'un employé'
                ));
            }

            return redirect()
                ->route('admin.paiement-salaires.index')
                ->with('success', "Paiement #{$paiementSalaire->id} mis à jour avec succès.");

        } catch (Throwable $e) {
            Log::error('Erreur MAJ paiement salaire', [
                'id'    => $paiementSalaire->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour.');
        }
    }

    /**
     * Suppression douce (soft delete) → corbeille.
     */
    public function destroy(PaiementSalaire $paiementSalaire): RedirectResponse
    {
        $paiementId = $paiementSalaire->id;

        if (!$paiementSalaire->peutEtreSupprime()) {
            return back()->with('error', "Impossible de supprimer ce paiement : des remboursements d'avance y sont associés.");
        }

        try {
            $this->paiementService->supprimerPaiement($paiementSalaire);

            Log::debug('Paiement de salaire mis en corbeille', [
                'paiement_id' => $paiementId,
                'admin_id'    => auth()->id(),
            ]);

            return redirect()
                ->route('admin.paiement-salaires.index')
                ->with('success', "Paiement #{$paiementId} déplacé dans la corbeille. Les dettes associées ont été restaurées.");

        } catch (Throwable $e) {
            Log::error('Erreur suppression paiement salaire', [
                'paiement_id' => $paiementId,
                'error'       => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible de supprimer ce paiement.');
        }
    }

    // ============================================================
    // RESTORE / FORCE DELETE (corbeille)
    // ============================================================

    /**
     * Restaure un paiement depuis la corbeille.
     */
    public function restaurer(int $id): RedirectResponse
    {
        $paiement = PaiementSalaire::onlyTrashed()->findOrFail($id);

        try {
            $paiement->restore();

            Log::debug('Paiement de salaire restauré', [
                'paiement_id' => $paiement->id,
                'admin_id'    => auth()->id(),
            ]);

            return redirect()
                ->route('admin.paiement-salaires.index', ['corbeille' => 1])
                ->with('success', "Paiement #{$paiement->id} restauré avec succès.");

        } catch (Throwable $e) {
            Log::error('Erreur restauration paiement salaire', [
                'paiement_id' => $id,
                'error'       => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible de restaurer ce paiement.');
        }
    }

    /**
     * Supprime définitivement un paiement de la corbeille.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $paiement = PaiementSalaire::onlyTrashed()->findOrFail($id);

        if ($paiement->remboursementsAvances()->exists()) {
            return back()->with('error', "Impossible de supprimer définitivement : des remboursements d'avance sont encore liés.");
        }

        try {
            $paiementId = $paiement->id;
            $paiement->forceDelete();

            Log::warning('Paiement de salaire supprimé définitivement', [
                'paiement_id' => $paiementId,
                'admin_id'    => auth()->id(),
            ]);

            return redirect()
                ->route('admin.paiement-salaires.index', ['corbeille' => 1])
                ->with('success', "Paiement #{$paiementId} supprimé définitivement.");

        } catch (Throwable $e) {
            Log::error('Erreur suppression définitive paiement salaire', [
                'paiement_id' => $id,
                'error'       => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible de supprimer définitivement ce paiement.');
        }
    }

    // ============================================================
    // EXPORTS
    // ============================================================

    public function exportPdf(Request $request): Response
    {
        return $this->exportService->exporter(
            'paiements-salaires',
            'pdf',
            $this->getExportData($request)
        );
    }

    public function exportCsv(Request $request): Response
    {
        return $this->exportService->exporter(
            'paiements-salaires',
            'csv',
            $this->getExportData($request)
        );
    }

    public function exportXml(Request $request): Response
    {
        return $this->exportService->exporter(
            'paiements-salaires',
            'xml',
            $this->getExportData($request)
        );
    }

    public function exportWord(Request $request): Response
    {
        return $this->exportService->exporter(
            'paiements-salaires',
            'word',
            $this->getExportData($request)
        );
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Construit la requête filtrée.
     *
     * @param  bool  $corbeille  Si true → uniquement les paiements supprimés
     */
    private function getFilteredQuery(Request $request, bool $corbeille = false): Builder
    {
        $query = PaiementSalaire::with(['user', 'moisScolaire']);

        // ✅ Gestion de la corbeille
        if ($corbeille) {
            $query->onlyTrashed();
        }
        // Sinon : comportement par défaut (exclut les supprimés grâce à SoftDeletes)

        return $query
            ->when($request->filled('user_id'),
                fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('mois_scolaire_id'),
                fn ($q) => $q->where('mois_scolaire_id', $request->input('mois_scolaire_id')))
            ->when($request->filled('statut'),
                fn ($q) => $q->where('statut', $request->input('statut')))
            ->when($request->filled('section_id'), function ($q) use ($request) {
                $sectionId = $request->input('section_id');
                $q->whereHas('user', function ($sub) use ($sectionId) {
                    if ($sectionId === 'sans_section') {
                        $sub->whereNull('section_id');
                    } else {
                        $sub->where('section_id', $sectionId);
                    }
                });
            });
    }

    /**
     * Récupère les données complètes pour l'export.
     */
    private function getExportData(Request $request): Collection
    {
        return $this->getFilteredQuery($request)
            ->orderByDesc('date_paiement')
            ->get();
    }

    /**
     * Statistiques par mois.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getStatsParMois(Builder $baseQuery): array
    {
        return (clone $baseQuery)
            ->reorder()
            ->selectRaw('
                mois_scolaire_id,
                SUM(montant_paye_usd) AS total_usd,
                SUM(montant_paye_fc)  AS total_fc,
                COUNT(*)              AS count,
                AVG(montant_paye_usd) AS moyenne_usd
            ')
            ->groupBy('mois_scolaire_id')
            ->with('moisScolaire:id,mois,nom_mois')
            ->get()
            ->map(function ($item) {
                $mois = $item->moisScolaire;

                return [
                    'nom'         => $mois?->nom_mois ?? $mois?->mois ?? 'Sans mois',
                    'total_usd'   => (int) $item->total_usd,
                    'total_fc'    => (int) $item->total_fc,
                    'count'       => (int) $item->count,
                    'moyenne_usd' => (int) round($item->moyenne_usd ?? 0),
                ];
            })
            ->sortByDesc('total_usd')
            ->values()
            ->all();
    }

    /**
     * Données utilisateur pour les formulaires.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getUsersData(): array
    {
        $users    = User::orderBy('name')->get();
        $moisList = MoisScolaire::orderBy('mois')->get(['id']);

        $dettesParMois = AvanceSalaire::whereIn('user_id', $users->pluck('id'))
            ->whereIn('statut', AvanceSalaire::STATUTS_ACTIFS)
            ->selectRaw('user_id, mois_scolaire_id, SUM(dette_restante_usd) AS total_dette')
            ->groupBy('user_id', 'mois_scolaire_id')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($items) => $items->pluck('total_dette', 'mois_scolaire_id')->all());

        $usersDataService = collect($this->avanceService->getUsersData($users))->keyBy('id');

        return $users->map(function (User $u) use ($moisList, $dettesParMois, $usersDataService) {
            $serviceData = $usersDataService[$u->id] ?? [
                'salaire'          => 0,
                'dette'            => 0,
                'limite'           => 0,
                'reste_disponible' => 0,
                'bloque'           => false,
            ];

            $dettesParMoisUser = $dettesParMois[$u->id] ?? [];
            $dettesComplet     = [];

            foreach ($moisList as $m) {
                $dettesComplet[$m->id] = (int) ($dettesParMoisUser[$m->id] ?? 0);
            }

            return [
                'id'                    => $u->id,
                'name'                  => $u->name,
                'type_salaire'          => $u->type_salaire,
                'salaire_mensuel_usd'   => (int) ($u->salaire_mensuel_usd ?? 0),
                'salaire_ajuste_usd'    => (int) ($u->salaire_ajuste_usd ?? 0),
                'salaire_auto_base_usd' => (int) ($u->salaire_auto_base_usd ?? 0),
                'salaire_attendu_usd'   => $serviceData['salaire'],
                'dette_totale_usd'      => $serviceData['dette'],
                'net_apres_dette_usd'   => max(0, $serviceData['salaire'] - $serviceData['dette']),
                'dettes_par_mois'       => $dettesComplet,
            ];
        })->values()->all();
    }
}