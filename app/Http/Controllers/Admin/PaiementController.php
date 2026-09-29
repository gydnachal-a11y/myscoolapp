<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaiementRequest;
use App\Http\Requests\UpdatePaiementRequest;
use App\Models\AnneeScolaire;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\SalleDeClasse;
use App\Models\Section;
use App\Models\TrancheScolaire;
use App\Services\PaiementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PaiementController extends Controller
{
    private const PER_PAGE           = 15;
    private const PER_PAGE_MIN       = 5;
    private const PER_PAGE_MAX       = 100;
    private const MODE_MENSUEL       = Paiement::MODE_MENSUEL;
    private const MODE_TRANCHE       = Paiement::MODE_TRANCHE;
    private const PAIEMENT_FERME_MSG = 'La session de paiement est fermée pour cette année scolaire.';

    private PaiementService $paiementService;

    public function __construct(PaiementService $paiementService)
    {
        $this->paiementService = $paiementService;
    }

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Request $request): View|RedirectResponse
    {
        $anneeActive = $this->getAnneeActiveOrRedirect();
        if ($anneeActive instanceof RedirectResponse) return $anneeActive;

        $validated = $request->validate([
            'mode_paiement' => ['nullable', 'in:' . self::MODE_MENSUEL . ',' . self::MODE_TRANCHE],
            'periode'       => ['nullable', 'integer', 'min:1'],
            'salle'         => ['nullable', 'integer', 'exists:salles_de_classe,id'],
            'statut'        => ['nullable', 'in:' . implode(',', Paiement::STATUTS)],
            'recherche'     => ['nullable', 'string', 'max:100'],
            'per_page'      => ['nullable', 'integer', 'min:' . self::PER_PAGE_MIN, 'max:' . self::PER_PAGE_MAX],
        ]);

        $perPage = (int) ($validated['per_page'] ?? self::PER_PAGE);

        $filteredQuery = $this->getFilteredQuery($request, $anneeActive);

        $paiements = (clone $filteredQuery)
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $statsGlobales = $this->getStatsGlobales($filteredQuery);
        $statsMois     = $this->getStatsParPeriode($filteredQuery);

        $salleId       = $validated['salle'] ?? null;
        $statsParSalle = $this->paiementService->getStatistiquesParSalle($anneeActive, $salleId);

        $salles = SalleDeClasse::query()
            ->when(!empty($validated['mode_paiement']), fn (Builder $q) => $q->where('mode_paiement', $validated['mode_paiement']))
            ->orderBy('nom')
            ->get(['id', 'nom', 'mode_paiement']);

        $mois = MoisScolaire::query()
            ->where('annee_scolaire_id', $anneeActive->id)
            ->orderBy('mois')
            ->get(['id', 'mois', 'nom_mois']);

        $tranches = TrancheScolaire::query()
            ->where('annee_scolaire_id', $anneeActive->id)
            ->orderBy('tranche')
            ->get(['id', 'tranche']);

        $tauxChange = $this->paiementService->getTauxChangeSafe();

        return view('admin.paiements.index', array_merge(
            compact('paiements', 'salles', 'anneeActive', 'mois', 'tranches', 'tauxChange', 'statsMois', 'statsParSalle'),
            $statsGlobales
        ));
    }

    // ============================================================
    // SHOW
    // ============================================================

    public function show(Paiement $paiement): View|RedirectResponse
    {
        $anneeActive = $this->getAnneeActiveOrRedirect();
        if ($anneeActive instanceof RedirectResponse) return $anneeActive;

        if ($redirect = $this->verifierAppartientAnneeActive($paiement, $anneeActive)) return $redirect;

        $paiement->load(['eleve', 'salleClasse', 'anneeScolaire', 'sessionPaiement']);

        return view('admin.paiements.show', [
            'paiement'   => $paiement,
            'tauxChange' => $this->paiementService->getTauxChangeSafe(),
        ]);
    }

    // ============================================================
    // CREATE / CREATE-MULTIPLE
    // ============================================================

    public function create(Request $request): View|RedirectResponse
    {
        return $this->renderFormView('admin.paiements.create', $request);
    }

    public function createMultiple(Request $request): View|RedirectResponse
    {
        return $this->renderFormView('admin.paiements.create-multiple', $request);
    }

    // ============================================================
    // STORE
    // ============================================================

    public function store(StorePaiementRequest $request): RedirectResponse
    {
        $anneeActive = $this->getAnneeActiveOrRedirect();
        if ($anneeActive instanceof RedirectResponse) return $anneeActive;

        if (!$this->verifierPaiementOuvert($anneeActive)) {
            return back()->withInput()->with('error', self::PAIEMENT_FERME_MSG);
        }

        $data       = $request->validated();
        $isMultiple = isset($data['eleve_ids']) && count($data['eleve_ids']) > 1;

        try {
            return $isMultiple
                ? $this->storeMultiple($data, $anneeActive, $request)
                : $this->storeSimple($data, $anneeActive, $request);

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();

        } catch (Throwable $e) {
            Log::error('Erreur création paiement', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'user_id'   => $request->user()?->id,
            ]);

            return back()->with('error', "Une erreur est survenue lors de l'enregistrement. Veuillez réessayer.")->withInput();
        }
    }

    // ============================================================
    // EDIT / UPDATE
    // ============================================================

    public function edit(Paiement $paiement): View|RedirectResponse
    {
        $anneeActive = $this->getAnneeActiveOrRedirect();
        if ($anneeActive instanceof RedirectResponse) return $anneeActive;

        if ($redirect = $this->verifierAppartientAnneeActive($paiement, $anneeActive, 'modifier')) return $redirect;

        $data = $this->buildFormData($anneeActive);
        $data['paiement'] = $paiement->load(['eleve', 'salleClasse', 'anneeScolaire', 'sessionPaiement']);

        return view('admin.paiements.edit', $data);
    }

    public function update(UpdatePaiementRequest $request, Paiement $paiement): RedirectResponse
    {
        $anneeActive = $this->getAnneeActiveOrRedirect();
        if ($anneeActive instanceof RedirectResponse) return $anneeActive;

        if ($redirect = $this->verifierAppartientAnneeActive($paiement, $anneeActive, 'modifier')) return $redirect;

        if (!$this->verifierPaiementOuvert($anneeActive)) {
            return back()->withInput()->with('error', self::PAIEMENT_FERME_MSG);
        }

        $data = $request->validated();

        try {
            $this->paiementService->mettreAJourPaiement($paiement, $data, $anneeActive);

            Log::info('Paiement mis à jour', [
                'paiement_id'     => $paiement->id,
                'user_id'         => $request->user()?->id,
                'champs_modifies' => array_keys($data),
            ]);

            return redirect()->route('admin.paiements.index')->with('success', 'Paiement mis à jour avec succès.');

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();

        } catch (Throwable $e) {
            Log::error('Erreur mise à jour paiement #' . $paiement->id, [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'user_id'   => $request->user()?->id,
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la mise à jour. Veuillez réessayer.')->withInput();
        }
    }

    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(Paiement $paiement): RedirectResponse
    {
        $anneeActive = $this->getAnneeActiveOrRedirect();
        if ($anneeActive instanceof RedirectResponse) return $anneeActive;

        if ($redirect = $this->verifierAppartientAnneeActive($paiement, $anneeActive, 'supprimer')) return $redirect;

        $eleveNom    = $paiement->eleve?->nom ?? 'Élève supprimé';
        $elevePrenom = $paiement->eleve?->prenom ?? '';
        $periode     = $paiement->periode ?? 'Sans période';
        $paiementId  = $paiement->id;

        try {
            $this->paiementService->supprimerPaiement($paiement, $anneeActive);

            Log::info('Paiement supprimé', [
                'paiement_id' => $paiementId,
                'eleve'       => trim("{$eleveNom} {$elevePrenom}"),
                'periode'     => $periode,
            ]);

            return redirect()->route('admin.paiements.index')->with('success', sprintf(
                'Paiement de %s %s (%s) supprimé avec succès.',
                $eleveNom,
                $elevePrenom,
                $periode
            ));

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());

        } catch (Throwable $e) {
            Log::error('Erreur suppression paiement #' . $paiementId, [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Impossible de supprimer ce paiement. Veuillez réessayer.');
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES — CONSTRUCTION DES DONNÉES
    // ============================================================

    private function buildFormData(AnneeScolaire $anneeActive): array
    {
        /* Salles avec section + session (via section) */
        $sallesData = SalleDeClasse::query()
            ->with(['section:id,nom,session_id', 'section.session:id,nom'])
            ->orderBy('nom')
            ->get()
            ->map(fn (SalleDeClasse $s): array => [
                'id'                      => $s->id,
                'nom'                     => $s->nom,
                'section_id'              => $s->section_id,
                'section'                 => $s->section?->nom,
                'session'                 => $s->section?->session?->nom,
                'mode_paiement'           => $s->mode_paiement,
                'frais_scolarite_mensuel' => (float) ($s->frais_scolarite_mensuel ?? 0),
                'frais_par_tranche'       => (float) ($s->frais_par_tranche ?? 0),
            ])
            ->values()
            ->all();

        $sections = Section::query()
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Section $s): array => ['id' => $s->id, 'nom' => $s->nom])
            ->values()
            ->all();

        $elevesParSalle = $this->paiementService->getElevesParSalle($anneeActive);

        $mois = MoisScolaire::query()
            ->where('annee_scolaire_id', $anneeActive->id)
            ->orderBy('mois')
            ->get(['id', 'nom_mois'])
            ->map(fn (MoisScolaire $m): array => ['value' => $m->id, 'label' => $m->nom_mois])
            ->values()
            ->all();

        $tranches = TrancheScolaire::query()
            ->where('annee_scolaire_id', $anneeActive->id)
            ->orderBy('tranche')
            ->get(['id', 'tranche'])
            ->map(fn (TrancheScolaire $t): array => ['value' => $t->id, 'label' => "Tranche {$t->tranche}"])
            ->values()
            ->all();

        $tauxChange = (float) $this->paiementService->getTauxChangeSafe();

        return compact('sallesData', 'sections', 'elevesParSalle', 'mois', 'tranches', 'tauxChange', 'anneeActive');
    }

    private function renderFormView(string $view, Request $request): View|RedirectResponse
    {
        $anneeActive = $this->getAnneeActiveOrRedirect();
        if ($anneeActive instanceof RedirectResponse) return $anneeActive;

        return view($view, $this->buildFormData($anneeActive));
    }

    // ============================================================
    // MÉTHODES PRIVÉES — VÉRIFICATIONS
    // ============================================================

    private function verifierAppartientAnneeActive(
        Paiement $paiement,
        AnneeScolaire $anneeActive,
        string $action = 'consulter'
    ): ?RedirectResponse {
        if ((int) $paiement->annee_scolaire_id === (int) $anneeActive->id) return null;

        return redirect()->route('admin.paiements.index')->with('error', sprintf(
            "Impossible de %s un paiement d'une autre année scolaire.",
            $action
        ));
    }

    private function verifierPaiementOuvert(AnneeScolaire $anneeActive): bool
    {
        return (bool) ($anneeActive->paiement_ouvert ?? false);
    }

    // ============================================================
    // MÉTHODES PRIVÉES — STORE
    // ============================================================

    private function storeMultiple(array $data, AnneeScolaire $anneeActive, Request $request): RedirectResponse
    {
        $paiementsCrees = $this->paiementService->creerPaiementsMultiples($data, $anneeActive);

        Log::info('Paiements groupés créés', [
            'nombre'   => count($paiementsCrees),
            'salle_id' => $data['salle_classe_id'] ?? null,
            'user_id'  => $request->user()?->id,
        ]);

        return redirect()->route('admin.paiements.index')
            ->with('success', count($paiementsCrees) . ' paiement(s) enregistré(s) avec succès.');
    }

    private function storeSimple(array $data, AnneeScolaire $anneeActive, Request $request): RedirectResponse
    {
        if (isset($data['eleve_ids']) && count($data['eleve_ids']) === 1) {
            $data['eleve_id'] = $data['eleve_ids'][0];
            unset($data['eleve_ids']);
        }

        /* Vérification de doublon AVEC type_periode */
        if ($this->paiementService->paiementExiste(
            (int) $data['eleve_id'],
            (int) $anneeActive->id,
            $data['type_periode'] ?? self::MODE_MENSUEL,
            $data['periode'] ?? null
        )) {
            return back()->with('error', 'Un paiement existe déjà pour cet élève sur cette période.')->withInput();
        }

        $paiement = $this->paiementService->creerPaiement($data, $anneeActive);

        Log::info('Paiement créé', [
            'paiement_id'         => $paiement->id,
            'eleve_id'            => $paiement->eleve_id,
            'session_paiement_id' => $paiement->session_paiement_id,
            'user_id'             => $request->user()?->id,
        ]);

        return redirect()->route('admin.paiements.index')->with('success', 'Paiement enregistré avec succès.');
    }

    // ============================================================
    // MÉTHODES PRIVÉES — REQUÊTES
    // ============================================================

    private function getAnneeActiveOrRedirect(): AnneeScolaire|RedirectResponse
    {
        $anneeActive = $this->paiementService->getAnneeActive();

        if (!$anneeActive) {
            return redirect()->route('admin.annees-scolaires.index')
                ->with('error', 'Aucune année scolaire active. Veuillez en définir une.');
        }

        return $anneeActive;
    }

    private function getFilteredQuery(Request $request, AnneeScolaire $anneeActive): Builder
    {
        $query = Paiement::query()
            ->with(['eleve', 'salleClasse', 'anneeScolaire', 'sessionPaiement'])
            ->where('annee_scolaire_id', $anneeActive->id);

        if ($request->filled('mode_paiement')) $query->where('type_periode', $request->input('mode_paiement'));
        if ($request->filled('periode'))       $query->where('periode', $request->input('periode'));
        if ($request->filled('salle'))         $query->where('salle_classe_id', $request->input('salle'));
        if ($request->filled('statut'))        $query->where('statut', $request->input('statut'));
        if ($request->filled('recherche'))     $query->recherche($request->input('recherche'));

        return $query;
    }

    // ============================================================
    // MÉTHODES PRIVÉES — STATISTIQUES
    // ============================================================

    private function getStatsGlobales(Builder $filteredQuery): array
    {
        $raw = (clone $filteredQuery)
            ->selectRaw('
                COALESCE(SUM(montant_paye_usd), 0)              AS total_paye_usd,
                COALESCE(SUM(montant_paye_fc), 0)               AS total_paye_fc,
                SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END)     AS nb_paye,
                SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END)     AS nb_partiel,
                SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END)     AS nb_impaye,
                SUM(CASE WHEN statut = ? THEN 1 ELSE 0 END)     AS nb_surpaye
            ', [Paiement::STATUT_PAYE, Paiement::STATUT_PARTIEL, Paiement::STATUT_IMPAYE, Paiement::STATUT_SURPAYE])
            ->first();

        return [
            'totalPayeUSD' => (float) ($raw->total_paye_usd ?? 0),
            'totalPayeFC'  => (float) ($raw->total_paye_fc  ?? 0),
            'nbPaye'       => (int)   ($raw->nb_paye        ?? 0),
            'nbPartiel'    => (int)   ($raw->nb_partiel     ?? 0),
            'nbImpaye'     => (int)   ($raw->nb_impaye      ?? 0),
            'nbSurpaye'    => (int)   ($raw->nb_surpaye     ?? 0),
        ];
    }

    private function getStatsParPeriode(Builder $filteredQuery): array
    {
        return (clone $filteredQuery)
            ->selectRaw('
                periode,
                SUM(montant_paye_usd) AS total_usd,
                SUM(montant_paye_fc)  AS total_fc,
                COUNT(*)              AS count,
                AVG(montant_paye_usd) AS moyenne_usd
            ')
            ->groupBy('periode')
            ->get()
            ->map(fn ($item) => [
                'nom'         => $item->periode ?? 'Sans période',
                'total_usd'   => (int) ($item->total_usd ?? 0),
                'total_fc'    => (int) ($item->total_fc  ?? 0),
                'count'       => (int) ($item->count     ?? 0),
                'moyenne_usd' => (int) round((float) ($item->moyenne_usd ?? 0)),
            ])
            ->sortByDesc('total_usd')
            ->values()
            ->toArray();
    }
}