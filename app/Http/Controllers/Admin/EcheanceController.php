<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Echeance;
use App\Services\EcheanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EcheanceController extends Controller
{
    public function __construct(
        private EcheanceService $echeanceService
    ) {}

    /**
     * Affiche la liste des échéances avec filtres, statistiques et pagination.
     */
    public function index(Request $request): View
    {
        $query = Echeance::query()
            ->with(['eleve', 'salleClasse', 'anneeScolaire'])
            ->orderBy('date_echeance');

        // Filtre par année scolaire
        if ($request->filled('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }

        // Filtre par salle de classe
        if ($request->filled('salle_classe_id')) {
            $query->where('salle_classe_id', $request->salle_classe_id);
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $now = now();
            switch ($request->statut) {
                case 'paye':
                    $query->where('est_paye', true);
                    break;
                case 'en_attente':
                    $query->where('est_paye', false)
                          ->where('date_echeance', '>=', $now);
                    break;
                case 'retard':
                    $query->where('est_paye', false)
                          ->where('date_echeance', '<', $now);
                    break;
            }
        }

        // Recherche par élève
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('eleve', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%");
            });
        }

        $echeances = $query->paginate(20)->appends($request->query());

        // Statistiques globales
        $totalEcheances = Echeance::count();
        $totalMontant = Echeance::sum('montant_usd');
        $totalPayes = Echeance::where('est_paye', true)->count();
        $totalEnAttente = Echeance::where('est_paye', false)
            ->where('date_echeance', '>=', now())
            ->count();
        $totalEnRetard = Echeance::where('est_paye', false)
            ->where('date_echeance', '<', now())
            ->count();

        // Listes pour les filtres
        $anneesScolaires = \App\Models\AnneeScolaire::orderBy('date_debut', 'desc')->get();
        $sallesClasse = \App\Models\SalleDeClasse::orderBy('nom')->get();

        return view('admin.echeances.index', compact(
            'echeances',
            'totalEcheances',
            'totalMontant',
            'totalPayes',
            'totalEnAttente',
            'totalEnRetard',
            'anneesScolaires',
            'sallesClasse'
        ));
    }

    /**
     * Génère les échéances pour l'année scolaire active.
     */
    public function generer(): RedirectResponse
    {
        try {
            $count = $this->echeanceService->genererPourAnneeActive();

            Log::info('Échéances générées', [
                'user_id' => auth()->id(),
                'count' => $count,
            ]);

            return back()->with('success', "$count échéance(s) générée(s).");
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération des échéances', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la génération des échéances.');
        }
    }

    /**
     * Supprime toutes les échéances.
     */
    public function vider(): RedirectResponse
    {
        try {
            Echeance::truncate();

            Log::warning('Toutes les échéances ont été supprimées', [
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', 'Toutes les échéances ont été supprimées.');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression des échéances', [
                'message' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la suppression des échéances.');
        }
    }
}