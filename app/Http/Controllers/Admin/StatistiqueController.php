<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Devise;
use App\Models\SalleDeClasse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StatistiqueController extends Controller
{
    /**
     * Affiche le tableau de bord des statistiques.
     */
    public function index(Request $request): \Illuminate\View\View
    {
        // 1. Taux de change USD → CDF
        $taux = $this->getExchangeRate();

        // 2. Années scolaires pour le filtre
        $annees = AnneeScolaire::orderBy('date_debut', 'desc')->get();

        // 3. Année sélectionnée (par défaut : année non clôturée la plus récente, sinon la dernière)
        $anneeId = $request->input('annee_scolaire_id', $this->getDefaultAnneeId());
        $anneeSelectionnee = $anneeId ? AnneeScolaire::find($anneeId) : null;

        // 4. Statistiques par salle pour l'année choisie
        $statsParSalle = $this->getStatsParSalle($anneeId);

        // 5. Totaux globaux
        $totaux = $this->calculerTotaux($statsParSalle);

        // 6. Comparaison effectif attendu vs inscrit (global)
        $comparaisonGlobale = $this->comparerEffectifs($anneeSelectionnee, $totaux['totalInscriptions']);

        return view('admin.statistiques.index', compact(
            'statsParSalle',
            'taux',
            'annees',
            'anneeId',
            'anneeSelectionnee',
            'totaux',
            'comparaisonGlobale'
        ));
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Récupère le taux de change USD → CDF.
     */
    private function getExchangeRate(): float
    {
        $source = Devise::where('code', 'USD')->first();
        $cible  = Devise::where('code', 'CDF')->first();
        return ($source && $cible) ? $source->tauxVers($cible) : 2800.0;
    }

    /**
     * Retourne l'ID de l'année par défaut.
     * Priorité : année non clôturée la plus récente, sinon la dernière année.
     */
    private function getDefaultAnneeId(): ?int
    {
        $annee = AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->first();

        if (!$annee) {
            $annee = AnneeScolaire::latest('date_debut')->first();
        }

        return $annee?->id;
    }

    /**
     * Récupère les statistiques par salle pour une année donnée.
     * Si l'année est nulle, retourne une collection vide.
     */
    private function getStatsParSalle(?int $anneeId): Collection
    {
        if ($anneeId === null) {
            return collect();
        }

        return SalleDeClasse::with(['section', 'option'])
            ->withCount(['inscriptions as nb_inscriptions' => function ($q) use ($anneeId) {
                $q->where('annee_scolaire_id', $anneeId);
            }])
            ->withSum(['inscriptions as total_frais_inscription' => function ($q) use ($anneeId) {
                $q->where('annee_scolaire_id', $anneeId);
            }], 'frais_inscription_final')
            ->withSum(['inscriptions as total_frais_annuel' => function ($q) use ($anneeId) {
                $q->where('annee_scolaire_id', $anneeId);
            }], 'frais_annuel_final')
            ->orderBy('nom')
            ->get()
            ->map(function ($salle) {
                $capacite = $salle->capacite_max ?: 1;
                $tauxRemplissage = round(($salle->nb_inscriptions / $capacite) * 100, 1);
                $salle->taux_remplissage = $tauxRemplissage;
                $salle->indicateur = $this->determinerIndicateur($tauxRemplissage);
                $salle->indicateur_couleur = match ($salle->indicateur) {
                    'inférieur' => 'red',
                    'moyen'     => 'amber',
                    'supérieur' => 'green',
                };
                return $salle;
            });
    }

    /**
     * Calcule les totaux globaux à partir des statistiques par salle.
     */
    private function calculerTotaux(Collection $statsParSalle): array
    {
        return [
            'totalInscriptions'      => $statsParSalle->sum('nb_inscriptions'),
            'totalFraisInscription'  => $statsParSalle->sum('total_frais_inscription'),
            'totalFraisAnnuel'       => $statsParSalle->sum('total_frais_annuel'),
            'totalFrais'             => $statsParSalle->sum('total_frais_inscription') + $statsParSalle->sum('total_frais_annuel'),
        ];
    }

    /**
     * Compare l'effectif attendu avec l'effectif inscrit (global).
     */
    private function comparerEffectifs(?AnneeScolaire $annee, int $totalInscriptions): array
    {
        $effectifAttendu = $annee?->effectif_attendu ?? 0;
        $ecart = $totalInscriptions - $effectifAttendu;

        // Éviter la division par zéro
        $pourcentage = $effectifAttendu > 0
            ? round(($totalInscriptions / $effectifAttendu) * 100, 1)
            : 0;

        // Déterminer le niveau
        if ($pourcentage < 70) {
            $niveau = 'inférieur';
            $message = "L'effectif inscrit est inférieur à l'objectif.";
        } elseif ($pourcentage <= 90) {
            $niveau = 'moyen';
            $message = "L'effectif inscrit est dans la moyenne.";
        } else {
            $niveau = 'supérieur';
            $message = "L'effectif inscrit dépasse l'objectif !";
        }

        return [
            'effectif_attendu' => $effectifAttendu,
            'effectif_inscrit' => $totalInscriptions,
            'ecart'            => $ecart,
            'pourcentage'      => $pourcentage,
            'niveau'           => $niveau,
            'message'          => $message,
            'couleur'          => match ($niveau) {
                'inférieur' => 'text-red-600 bg-red-50',
                'moyen'     => 'text-amber-600 bg-amber-50',
                'supérieur' => 'text-green-600 bg-green-50',
            },
        ];
    }

    /**
     * Détermine l'indicateur en fonction du taux de remplissage.
     */
    private function determinerIndicateur(float $taux): string
    {
        if ($taux < 70) {
            return 'inférieur';
        }
        if ($taux <= 90) {
            return 'moyen';
        }
        return 'supérieur';
    }
}