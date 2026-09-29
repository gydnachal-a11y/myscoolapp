<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Devise;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\PaiementFraisSupplementaire;
use App\Models\PaiementSalaire;
use App\Models\SalleDeClasse;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PaiementDashboardController extends Controller
{
    private const CACHE_TTL = 3600;

    private function getAnneeActive(?int $anneeId = null): ?AnneeScolaire
    {
        if ($anneeId) {
            return AnneeScolaire::find($anneeId);
        }

        $cachedId = Cache::remember('annee_active_id', self::CACHE_TTL, function () {
            return AnneeScolaire::where('cloturee', false)->latest('date_debut')->value('id');
        });

        return $cachedId ? AnneeScolaire::find($cachedId) : null;
    }

    private function getTauxChange(): float
    {
        static $taux = null;
        if ($taux === null) {
            $source = Devise::where('code', 'USD')->first();
            $cible = Devise::where('code', 'CDF')->first();
            $taux = ($source && $cible) ? $source->tauxVers($cible) : 2800;
        }
        return $taux;
    }

    public function index(Request $request): View|RedirectResponse
    {
        $filters = $request->validate([
            'annee_scolaire_id' => 'nullable|exists:annees_scolaires,id',
            'section_id'        => 'nullable|exists:sections,id',
            'salle_classe_id'   => 'nullable|exists:salles_de_classe,id',
            'mois_scolaire_id'  => 'nullable|exists:mois_scolaires,id',
        ]);

        $annee = $this->getAnneeActive($filters['annee_scolaire_id'] ?? null);
        if (!$annee) {
            return redirect()->route('admin.dashboard')->with('error', 'Aucune année scolaire trouvée.');
        }

        $annees = AnneeScolaire::orderBy('date_debut', 'desc')->get();
        $sectionId = $filters['section_id'] ?? null;
        $salleId   = $filters['salle_classe_id'] ?? null;
        $moisId    = $filters['mois_scolaire_id'] ?? null;

        // Tous les mois de l'année (pour le filtre et l'affichage)
        $tousMois = MoisScolaire::where('annee_scolaire_id', $annee->id)->orderBy('mois')->get();
        $mois = $moisId ? $tousMois->where('id', $moisId) : $tousMois;
        $moisIds = $mois->pluck('id')->toArray();

        $sections = Section::orderBy('nom')->get();
        $salles = SalleDeClasse::when($sectionId, fn($q) => $q->where('section_id', $sectionId))
            ->orderBy('nom')->get();

        // ===================== ENTRÉES =====================
        // Paiements (sans les relations inutiles)
        $paiementsQuery = Paiement::where('annee_scolaire_id', $annee->id)
            ->when($sectionId, fn($q) => $q->whereHas('salleClasse', fn($sub) => $sub->where('section_id', $sectionId)))
            ->when($salleId, fn($q) => $q->where('salle_classe_id', $salleId))
            ->when($moisId, function ($q) use ($moisId) {
                $moisModel = MoisScolaire::find($moisId);
                if ($moisModel) {
                    $date = \Carbon\Carbon::parse($moisModel->mois);
                    $q->where(function ($sub) use ($moisModel, $date) {
                        $sub->where(function ($q) use ($moisModel) {
                            $q->where('type_periode', 'mensuel')->where('periode', $moisModel->mois);
                        })->orWhere(function ($q) use ($date) {
                            $q->where('type_periode', 'tranche')
                              ->whereYear('date_paiement', $date->year)
                              ->whereMonth('date_paiement', $date->month);
                        });
                    });
                }
            });

        $paiements = $paiementsQuery->get();

        // Frais supplémentaires (sans relations inutiles)
        $paiementsFraisSuppQuery = PaiementFraisSupplementaire::whereBetween('date_paiement', [$annee->date_debut, $annee->date_fin])
            ->when($sectionId || $salleId, function ($q) use ($annee, $sectionId, $salleId) {
                $q->whereHas('eleve.inscriptions', function ($sub) use ($annee, $sectionId, $salleId) {
                    $sub->where('annee_scolaire_id', $annee->id)
                        ->when($sectionId, fn($s) => $s->whereHas('salleDeClasse', fn($sc) => $sc->where('section_id', $sectionId)))
                        ->when($salleId, fn($s) => $s->where('salle_classe_id', $salleId));
                });
            })
            ->when($moisId, function ($q) use ($moisId) {
                $moisModel = MoisScolaire::find($moisId);
                if ($moisModel) {
                    $date = \Carbon\Carbon::parse($moisModel->mois);
                    $q->whereYear('date_paiement', $date->year)->whereMonth('date_paiement', $date->month);
                }
            });

        $paiementsFraisSupp = $paiementsFraisSuppQuery->get();

        // Inscriptions
        $inscriptionsQuery = Inscription::where('annee_scolaire_id', $annee->id)
            ->when($sectionId, fn($q) => $q->whereHas('salleDeClasse', fn($sub) => $sub->where('section_id', $sectionId)))
            ->when($salleId, fn($q) => $q->where('salle_classe_id', $salleId));

        $inscriptions = $inscriptionsQuery->get();
        $totalInscriptionsUSD = $inscriptions->sum('frais_inscription_final');

        // ===================== SORTIES (SALAIRES) =====================
        // Récupération unique des salaires avec jointure directe pour garantir le filtrage par année
        $paiementsSalaires = PaiementSalaire::join('mois_scolaires', 'paiement_salaires.mois_scolaire_id', '=', 'mois_scolaires.id')
            ->where('mois_scolaires.annee_scolaire_id', $annee->id)
            ->when($moisId, fn($q) => $q->where('paiement_salaires.mois_scolaire_id', $moisId))
            ->select('paiement_salaires.*')
            ->with(['user', 'moisScolaire'])
            ->get();

        $totalSortiesUSD = $paiementsSalaires->sum('montant_paye_usd');

        // Statistiques mensuelles des salaires
        $salairesMensuels = $paiementsSalaires
            ->groupBy('mois_scolaire_id')
            ->map(fn($group) => $group->sum('montant_paye_usd'));

        $personnelParMois = $paiementsSalaires
            ->groupBy('mois_scolaire_id')
            ->map(fn($group) => $group->map(fn($p) => [
                'nom' => $p->user->name,
                'salaire' => $p->montant_paye_usd,
            ])->values());

        // ===================== TOTAUX =====================
        $totalPaiementsUSD = $paiements->sum('montant_paye_usd');
        $totalFraisSuppUSD = $paiementsFraisSupp->sum('montant_paye_usd');
        $totalEntreesUSD = $totalPaiementsUSD + $totalInscriptionsUSD + $totalFraisSuppUSD;
        $soldeUSD = $totalEntreesUSD - $totalSortiesUSD;

        $tauxChange = $this->getTauxChange();
        $totalEntreesFC = round($totalEntreesUSD * $tauxChange);
        $totalSortiesFC = round($totalSortiesUSD * $tauxChange);
        $soldeFC = round($soldeUSD * $tauxChange);

        // ===================== DONNÉES MENSUELLES =====================
        // Agrégations en mémoire
        $paiementsMensuels = $paiements
            ->where('type_periode', 'mensuel')
            ->groupBy('periode')
            ->map(fn($group) => $group->sum('montant_paye_usd'));

        $paiementsTranches = $paiements
            ->where('type_periode', 'tranche')
            ->groupBy(fn($p) => $p->date_paiement->format('Y-m'))
            ->map(fn($group) => $group->sum('montant_paye_usd'));

        $fraisSuppMensuels = $paiementsFraisSupp
            ->groupBy(fn($p) => $p->date_paiement->format('Y-m'))
            ->map(fn($group) => $group->sum('montant_paye_usd'));

        // Répartition des frais d'inscription sur les mois de l'année
        // Pour que le solde mensuel reflète une entrée régulière, on divise le total par le nombre de mois
        $nbMois = $mois->count();
        $inscriptionParMois = ($nbMois > 0) ? (int) round($totalInscriptionsUSD / $nbMois) : 0;

        // Construction du tableau mensuel
        $dataMensuelle = [];
        foreach ($mois as $m) {
            $moisKey = $m->mois;
            $dateYmd = \Carbon\Carbon::parse($moisKey)->format('Y-m');

            $entreesMensuel = $paiementsMensuels[$moisKey] ?? 0;
            $entreesTranche = $paiementsTranches[$dateYmd] ?? 0;
            $entreesFraisSupp = $fraisSuppMensuels[$dateYmd] ?? 0;
            $entreesInscriptions = $inscriptionParMois;
            $sorties = $salairesMensuels[$m->id] ?? 0;
            $personnel = $personnelParMois[$m->id] ?? collect();

            $entrees = $entreesMensuel + $entreesTranche + $entreesFraisSupp + $entreesInscriptions;

            $dataMensuelle[] = [
                'mois' => $m->nom_mois ?? $moisKey,
                'entrees_mensuel' => (int) $entreesMensuel,
                'entrees_tranche' => (int) $entreesTranche,
                'entrees_inscriptions' => (int) $entreesInscriptions,
                'entrees_frais_supplementaire' => (int) $entreesFraisSupp,
                'entrees' => (int) $entrees,
                'sorties' => (int) $sorties,
                'personnel' => $personnel,
                'solde' => (int) ($entrees - $sorties),
            ];
        }

        // ===================== DÉTAILS PAR SALLE =====================
        $sallesDetails = $this->buildSallesDetails($sectionId, $salleId, $inscriptions, $paiements, $paiementsFraisSupp, $mois);

        return view('admin.paiement-dashboard.index', compact(
            'annee', 'annees', 'sections', 'sectionId', 'salleId', 'moisId',
            'salles', 'mois', 'tousMois',
            'dataMensuelle',
            'totalEntreesUSD', 'totalSortiesUSD', 'soldeUSD',
            'totalEntreesFC', 'totalSortiesFC', 'soldeFC',
            'sallesDetails', 'tauxChange'
        ));
    }

    private function buildSallesDetails($sectionId, $salleId, $inscriptions, $paiements, $paiementsFraisSupp, $mois)
    {
        $sallesCible = SalleDeClasse::when($sectionId, fn($q) => $q->where('section_id', $sectionId))
            ->when($salleId, fn($q) => $q->where('id', $salleId))
            ->with('section')
            ->get();

        $eleveSalleMap = $inscriptions->pluck('salle_classe_id', 'eleve_id')->toArray();

        return $sallesCible->map(function ($salle) use ($inscriptions, $paiements, $paiementsFraisSupp, $mois, $eleveSalleMap) {
            $inscriptionsSalle = $inscriptions->where('salle_classe_id', $salle->id);
            $paiementsSalle = $paiements->where('salle_classe_id', $salle->id);

            $fraisInscription = $inscriptionsSalle->sum('frais_inscription_final');
            $fraisAnnuel = $inscriptionsSalle->sum('frais_annuel_final');
            $paiementsMensuel = $paiementsSalle->where('type_periode', 'mensuel')->sum('montant_paye_usd');
            $paiementsTranche = $paiementsSalle->where('type_periode', 'tranche')->sum('montant_paye_usd');
            $fraisSupp = $paiementsFraisSupp->filter(fn($p) => ($eleveSalleMap[$p->eleve_id] ?? null) == $salle->id)->sum('montant_paye_usd');

            $totalAttendu = $fraisInscription + $fraisAnnuel;
            $totalPaye = $fraisInscription + $paiementsMensuel + $paiementsTranche + $fraisSupp;
            $taux = $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100, 1) : 0;

            $attenduMensuel = null;
            $attenduParTranche = null;
            if ($salle->mode_paiement === 'mensuel') {
                $attenduMensuel = $mois->count() > 0 ? round($fraisAnnuel / $mois->count(), 2) : 0;
            } elseif ($salle->mode_paiement === 'tranche') {
                $nbTranches = $salle->nombre_tranches ?? 1;
                $attenduParTranche = $nbTranches > 0 ? round($fraisAnnuel / $nbTranches, 2) : 0;
            }

            return [
                'salle' => $salle->nom,
                'section' => $salle->section->nom ?? '—',
                'mode_paiement' => $salle->mode_paiement,
                'nb_inscriptions' => $inscriptionsSalle->count(),
                'frais_inscription' => $fraisInscription,
                'frais_annuel' => $fraisAnnuel,
                'paiements_mensuel' => $paiementsMensuel,
                'paiements_tranche' => $paiementsTranche,
                'frais_supplementaires' => $fraisSupp,
                'total_attendu' => $totalAttendu,
                'total_paye' => $totalPaye,
                'taux_recouvrement' => $taux,
                'attendu_mensuel' => $attenduMensuel,
                'attendu_par_tranche' => $attenduParTranche,
            ];
        })->toArray();
    }
}