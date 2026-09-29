<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\MoisScolaire;
use App\Models\Option;
use App\Models\Paiement;
use App\Models\PaiementFraisSupplementaire;
use App\Models\SalleDeClasse;
use App\Models\Section;
use App\Models\Session;
use App\Models\TrancheScolaire;
use App\Services\ExportService;
use App\Services\InfoPaiementScolaireService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class InfoPaiementController extends Controller
{
    /**
     * Types d'export acceptés.
     */
    private const TYPE_PRINCIPAUX = 'principaux';
    private const TYPE_FRAIS      = 'frais';

    public function __construct(
        private InfoPaiementScolaireService $service,
        private ExportService $exportService
    ) {}

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Request $request): View
    {
        /* ---------- Colonnes robustes ---------- */
        $sessionCols = $this->safeColumns('sessions', ['id', 'nom'], ['section_id', 'description']);
        $sectionCols = $this->safeColumns('sections', ['id', 'nom'], ['session_id', 'description']);
        $salleCols   = $this->safeColumns('salles_de_classe', ['id', 'nom'], ['section_id', 'option_id', 'mode_paiement', 'session_id']);
        $optionCols  = $this->safeColumns('options', ['id', 'nom'], ['section_id', 'code']);

        /* ---------- Données de référence ---------- */
        $sessions = Session::orderBy('nom')->get($sessionCols);
        $sections = Section::orderBy('nom')->get($sectionCols);
        $salles   = SalleDeClasse::orderBy('nom')->get($salleCols);
        $options  = Option::orderBy('nom')->get($optionCols);

        /* ---------- Salle sélectionnée + mode ---------- */
        $salleId     = $request->integer('salle') ?: null;
        $salle       = $salleId ? SalleDeClasse::find($salleId) : null;
        $typePeriode = $salle?->mode_paiement;

        /* ---------- Année active ---------- */
        $anneeActive = AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->first();

        /* ---------- Périodes encaissées pour cette salle ---------- */
        $periodesIdsDisponibles = collect();

        if ($salle && $typePeriode && $anneeActive) {
            $periodesIdsDisponibles = Paiement::query()
                ->where('annee_scolaire_id', $anneeActive->id)
                ->where('salle_classe_id', $salle->id)
                ->where('type_periode', $typePeriode)
                ->whereNotNull('periode')
                ->distinct()
                ->pluck('periode')
                ->map(fn ($p) => (int) $p)
                ->values();
        }

        /* ---------- Reset période si invalide ---------- */
        if ($salle && $request->filled('periode')) {
            $periodeDemandee = (int) $request->input('periode');
            if (!$periodesIdsDisponibles->contains($periodeDemandee)) {
                $request->merge(['periode' => null]);
            }
        }

        /* ---------- Construction des périodes ---------- */
        if ($salle && $typePeriode) {
            if ($typePeriode === 'mensuel') {
                $periodes = MoisScolaire::select('id', 'nom_mois as nom', DB::raw("'mensuel' as type_periode"))
                    ->whereIn('id', $periodesIdsDisponibles)
                    ->orderBy('id')
                    ->get();
            } else {
                $periodes = TrancheScolaire::select('id', 'tranche as nom', DB::raw("'tranche' as type_periode"))
                    ->whereIn('id', $periodesIdsDisponibles)
                    ->orderBy('id')
                    ->get();
            }
        } else {
            $periodes = MoisScolaire::select('id', 'nom_mois as nom', DB::raw("'mensuel' as type_periode"))
                ->orderBy('id')
                ->get()
                ->concat(
                    TrancheScolaire::select('id', 'tranche as nom', DB::raw("'tranche' as type_periode"))
                        ->orderBy('id')
                        ->get()
                );
        }

        /* ---------- Requêtes principales ---------- */
        $queryPrincipaux = $this->service->getPaiementsPrincipauxQuery($request);
        $queryFrais      = $this->service->getFraisSupplementairesQuery($request);

        /* ---------- Totaux ---------- */
        $totauxPrincipaux = (clone $queryPrincipaux)->selectRaw('
            COALESCE(SUM(montant_paye_usd), 0)    AS total_paye_usd,
            COALESCE(SUM(montant_paye_fc), 0)     AS total_paye_fc,
            COALESCE(SUM(montant_restant_fc), 0)  AS total_restant_fc,
            COUNT(*)                              AS total_count
        ')->first();

        $totauxFrais = (clone $queryFrais)->selectRaw('
            COALESCE(SUM(montant_paye_usd), 0) AS total_paye_usd,
            COALESCE(SUM(montant_paye_fc), 0)  AS total_paye_fc,
            COUNT(*)                            AS total_count
        ')->first();

        /* ---------- Pagination ---------- */
        $paiementsPrincipaux = (clone $queryPrincipaux)
            ->orderByDesc('date_paiement')
            ->paginate(20, ['*'], 'page_principaux')
            ->withQueryString();

        $fraisSupplementaires = (clone $queryFrais)
            ->orderByDesc('date_paiement')
            ->paginate(20, ['*'], 'page_frais')
            ->withQueryString();

        return view('admin.info-paiements.index', [
            'sessions'                => $sessions,
            'sections'                => $sections,
            'salles'                  => $salles,
            'options'                 => $options,
            'periodes'                => $periodes,
            'salle'                   => $salle,
            'typePeriode'             => $typePeriode,
            'periodesIdsDisponibles'  => $periodesIdsDisponibles,
            'paiementsPrincipaux'     => $paiementsPrincipaux,
            'fraisSupplementaires'    => $fraisSupplementaires,
            'totalPaiements'          => $totauxPrincipaux->total_count,
            'totalFraisSupp'          => $totauxFrais->total_count,
            'totalPayePrincipal'      => (float) $totauxPrincipaux->total_paye_usd,
            'totalPayeFraisSupp'      => (float) $totauxFrais->total_paye_usd,
            'totalPayePrincipalFC'    => (float) $totauxPrincipaux->total_paye_fc,
            'totalPayeFraisSuppFC'    => (float) $totauxFrais->total_paye_fc,
            'totalRestantPrincipalFC' => (float) $totauxPrincipaux->total_restant_fc,
        ]);
    }

    // ============================================================
    // REÇUS
    // ============================================================

    public function recuPaiement(Paiement $paiement): View
    {
        $paiement = $this->service->preparerRecuPaiementPrincipal($paiement);

        return view('admin.info-paiements.recu', [
            'paiement' => $paiement,
            'type'     => 'principal',
        ]);
    }

    public function recuFraisSupplementaire(PaiementFraisSupplementaire $paiement): View
    {
        $paiement = $this->service->preparerRecuFraisSupplementaire($paiement);

        return view('admin.info-paiements.recu', [
            'paiement' => $paiement,
            'type'     => 'frais_supplementaire',
        ]);
    }

    // ============================================================
    // EXPORTS — Dynamiques selon `type` (principaux | frais)
    // ============================================================

    public function exportPdfPaiements(Request $request): Response
    {
        return $this->export($request, 'pdf');
    }

    public function exportCsvPaiements(Request $request): Response
    {
        return $this->export($request, 'csv');
    }

    public function exportXmlPaiements(Request $request): Response
    {
        return $this->export($request, 'xml');
    }

    public function exportWordPaiements(Request $request): Response
    {
        return $this->export($request, 'word');
    }

    /**
     * Imprime les données filtrées (page HTML dédiée).
     */
    public function imprimerPaiements(Request $request): View
    {
        $type = $this->resolveType($request);

        $paiements = $this->queryByType($request, $type)
            ->orderByDesc('date_paiement')
            ->get();

        return view('admin.info-paiements.imprimer_paiements', [
            'paiements' => $paiements,
            'type'      => $type,
        ]);
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Export factorisé : choisit la requête et le template selon `type`.
     */
    private function export(Request $request, string $format): Response
    {
        $type = $this->resolveType($request);

        $data = $this->queryByType($request, $type)
            ->orderByDesc('date_paiement')
            ->get();

        $viewName = $type === self::TYPE_FRAIS
            ? 'frais-supplementaires'
            : 'paiements-principaux';

        return $this->exportService->exporter($viewName, $format, $data);
    }

    /**
     * Détermine le type d'export depuis la requête.
     */
    private function resolveType(Request $request): string
    {
        $type = (string) $request->input('type', self::TYPE_PRINCIPAUX);

        return in_array($type, [self::TYPE_PRINCIPAUX, self::TYPE_FRAIS], true)
            ? $type
            : self::TYPE_PRINCIPAUX;
    }

    /**
     * Retourne la bonne requête selon le type.
     */
    private function queryByType(Request $request, string $type): Builder
    {
        return $type === self::TYPE_FRAIS
            ? $this->service->getFraisSupplementairesQuery($request)
            : $this->service->getPaiementsPrincipauxQuery($request);
    }

    /**
     * Retourne les colonnes de base + celles optionnelles qui existent.
     *
     * @param  array<int, string>  $base
     * @param  array<int, string>  $optionnelles
     * @return array<int, string>
     */
    private function safeColumns(string $table, array $base, array $optionnelles): array
    {
        foreach ($optionnelles as $col) {
            if (Schema::hasColumn($table, $col)) {
                $base[] = $col;
            }
        }

        return $base;
    }
}