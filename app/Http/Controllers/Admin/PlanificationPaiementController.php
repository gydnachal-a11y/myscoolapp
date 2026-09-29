<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionPaiementRequest;
use App\Http\Requests\UpdateSessionPaiementRequest;
use App\Models\AnneeScolaire;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\SalleDeClasse;
use App\Models\SessionPaiement;
use App\Models\TrancheScolaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PlanificationPaiementController extends Controller
{
    // ============================================================
    // INDEX
    // ============================================================

    public function index(): View|RedirectResponse
    {
        $anneeActive = $this->getAnneeActiveOrRedirect();
        if ($anneeActive instanceof RedirectResponse) {
            return $anneeActive;
        }

        // IDs des périodes de l'année active
        $idsMois     = MoisScolaire::where('annee_scolaire_id', $anneeActive->id)->pluck('id');
        $idsTranches = TrancheScolaire::where('annee_scolaire_id', $anneeActive->id)->pluck('id');

        $sessions = SessionPaiement::with('salleClasse')
            ->where(function ($query) use ($idsMois, $idsTranches) {
                $query->where(function ($q) use ($idsMois) {
                    $q->where('type_periode', Paiement::MODE_MENSUEL)
                      ->whereIn('periode', $idsMois);
                })->orWhere(function ($q) use ($idsTranches) {
                    $q->where('type_periode', Paiement::MODE_TRANCHE)
                      ->whereIn('periode', $idsTranches);
                });
            })
            ->orderBy('date_debut_session')
            ->get();

        $salles = SalleDeClasse::orderBy('nom')->get(['id', 'nom', 'mode_paiement']);

        return view('admin.planification-paiements.index', compact('salles', 'sessions', 'anneeActive'));
    }

    // ============================================================
    // SHOW
    // ============================================================

    public function show(SalleDeClasse $salle): View|RedirectResponse
    {
        $anneeActive = $this->getAnneeActiveOrRedirect();
        if ($anneeActive instanceof RedirectResponse) {
            return $anneeActive;
        }

        if ($salle->mode_paiement === Paiement::MODE_MENSUEL) {
            $periodes = MoisScolaire::where('annee_scolaire_id', $anneeActive->id)
                ->orderBy('date_debut')
                ->get();
            $type = Paiement::MODE_MENSUEL;
        } else {
            $periodes = TrancheScolaire::where('annee_scolaire_id', $anneeActive->id)
                ->orderBy('date_debut')
                ->get();
            $type = Paiement::MODE_TRANCHE;
        }

        $sessions = SessionPaiement::where('salle_classe_id', $salle->id)
            ->get()
            ->keyBy('periode');

        return view('admin.planification-paiements.show', compact('salle', 'periodes', 'sessions', 'type'));
    }

    // ============================================================
    // STORE
    // ============================================================

    public function store(StoreSessionPaiementRequest $request, SalleDeClasse $salle): RedirectResponse
    {
        $anneeActive = $this->getAnneeActiveOrRedirect();
        if ($anneeActive instanceof RedirectResponse) {
            return $anneeActive;
        }

        $data = $request->validated();

        // ✅ Cohérence mode / type
        if ($data['type_periode'] !== $salle->mode_paiement) {
            return back()->with('error', "Le type de période ne correspond pas au mode de paiement de la salle ({$salle->mode_paiement}).");
        }

        // Doublon
        $existante = SessionPaiement::where([
            'salle_classe_id' => $salle->id,
            'type_periode'    => $data['type_periode'],
            'periode'         => $data['periode'],
        ])->exists();

        if ($existante) {
            return back()->with('error', 'Une session existe déjà pour cette période.');
        }

        try {
            $session = SessionPaiement::create([
                'salle_classe_id'    => $salle->id,
                'type_periode'       => $data['type_periode'],
                'periode'            => $data['periode'],
                'date_debut_session' => $data['date_debut_session'],
                'date_fin_session'   => $data['date_fin_session'],
            ]);

            Log::debug('Session paiement créée', [
                'session_id' => $session->id,
                'salle_id'   => $salle->id,
                'periode'    => $data['periode'],
                'admin_id'   => auth()->id(),
            ]);

            return redirect()
                ->route('admin.planification-paiements.show', $salle)
                ->with('success', 'Session de paiement planifiée avec succès.');

        } catch (\Throwable $e) {
            Log::error('Erreur création session paiement', [
                'salle_id' => $salle->id,
                'error'    => $e->getMessage(),
            ]);

            return back()->with('error', "Une erreur est survenue lors de la création de la session.");
        }
    }

    // ============================================================
    // PLANIFIER TOUTES — Version batch optimisée
    // ============================================================

    /**
     * Planifie automatiquement les sessions pour toutes les salles.
     *
     * ⚡ Optimisation : au lieu de N×(M+1) requêtes (existence + insertion),
     *    on fait :
     *      1. 1 requête pour précharger toutes les périodes
     *      2. 1 requête pour récupérer les combinaisons déjà existantes
     *      3. 1 INSERT BATCH (ou insertOrIgnore) pour tout créer en une fois
     */
    public function planifierToutes(): RedirectResponse
    {
        $anneeActive = $this->getAnneeActiveOrRedirect();
        if ($anneeActive instanceof RedirectResponse) {
            return $anneeActive;
        }

        $salles = SalleDeClasse::all(['id', 'mode_paiement']);

        if ($salles->isEmpty()) {
            return back()->with('info', 'Aucune salle à planifier.');
        }

        // Précharger les périodes (1 requête chacune)
        $mois = MoisScolaire::where('annee_scolaire_id', $anneeActive->id)
            ->orderBy('date_debut')
            ->get(['id', 'date_debut', 'date_fin']);

        $tranches = TrancheScolaire::where('annee_scolaire_id', $anneeActive->id)
            ->orderBy('date_debut')
            ->get(['id', 'date_debut', 'date_fin']);

        if ($mois->isEmpty() && $tranches->isEmpty()) {
            return back()->with('warning', "Aucune période (mois/tranche) n'est générée pour cette année. Générez-les d'abord.");
        }

        // Récupérer toutes les sessions déjà existantes pour cette année (1 requête)
        $dejaExistantes = SessionPaiement::whereIn('salle_classe_id', $salles->pluck('id'))
            ->whereIn('periode', $mois->pluck('id')->merge($tranches->pluck('id')))
            ->get(['salle_classe_id', 'type_periode', 'periode'])
            ->groupBy('salle_classe_id')
            ->map(fn ($items) => $items->map(fn ($i) => "{$i->type_periode}_{$i->periode}")->all())
            ->all();

        // Construire le batch d'insertion
        $now  = now();
        $rows = [];

        foreach ($salles as $salle) {
            $periodes = $salle->mode_paiement === Paiement::MODE_MENSUEL ? $mois : $tranches;
            $type     = $salle->mode_paiement;
            $cle      = $dejaExistantes[$salle->id] ?? [];

            foreach ($periodes as $periode) {
                if (in_array("{$type}_{$periode->id}", $cle, true)) {
                    continue;
                }

                $rows[] = [
                    'salle_classe_id'    => $salle->id,
                    'type_periode'       => $type,
                    'periode'            => $periode->id,
                    'date_debut_session' => $periode->date_debut,
                    'date_fin_session'   => $periode->date_fin,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];
            }
        }

        if (empty($rows)) {
            return back()->with('info', 'Toutes les sessions sont déjà planifiées. Rien à faire.');
        }

        try {
            DB::transaction(function () use ($rows) {
                // insertOrIgnore : ignore silencieusement les doublons (grâce à une contrainte UNIQUE)
                SessionPaiement::insertOrIgnore($rows);
            });

            $count = count($rows);

            Log::info('Planification globale paiements', [
                'sessions_crees' => $count,
                'admin_id'       => auth()->id(),
            ]);

            return redirect()
                ->route('admin.planification-paiements.index')
                ->with('success', "{$count} session(s) de paiement planifiée(s) automatiquement.");

        } catch (\Throwable $e) {
            Log::error('Erreur planification globale paiements', [
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "Une erreur est survenue lors de la planification automatique.");
        }
    }

    // ============================================================
    // UPDATE / DESTROY
    // ============================================================

    public function update(UpdateSessionPaiementRequest $request, SessionPaiement $session): RedirectResponse
    {
        // ✅ Vérifie que la session appartient à l'année active
        if (!$this->sessionAppartientAnneeActive($session)) {
            return back()->with('error', "Cette session n'appartient pas à l'année scolaire active.");
        }

        try {
            $session->update($request->validated());

            Log::debug('Session paiement mise à jour', [
                'session_id' => $session->id,
                'admin_id'   => auth()->id(),
            ]);

            return back()->with('success', 'Session mise à jour.');

        } catch (\Throwable $e) {
            Log::error('Erreur MAJ session paiement', [
                'session_id' => $session->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', "Une erreur est survenue lors de la mise à jour.");
        }
    }

    public function destroy(SessionPaiement $session): RedirectResponse
    {
        if (!$this->sessionAppartientAnneeActive($session)) {
            return back()->with('error', "Cette session n'appartient pas à l'année scolaire active.");
        }

        if ($session->paiements()->exists()) {
            return back()->with('error', 'Impossible de supprimer cette session car des paiements y sont associés.');
        }

        try {
            $session->delete();

            Log::debug('Session paiement supprimée', [
                'session_id' => $session->id,
                'admin_id'   => auth()->id(),
            ]);

            return back()->with('success', 'Session supprimée.');

        } catch (\Throwable $e) {
            Log::error('Erreur suppression session paiement', [
                'session_id' => $session->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', "Une erreur est survenue lors de la suppression.");
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    private function getAnneeActiveOrRedirect(): AnneeScolaire|RedirectResponse
    {
        $annee = AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->first();

        if (!$annee) {
            return redirect()
                ->route('admin.annees-scolaires.index')
                ->with('error', 'Aucune année scolaire active.');
        }

        return $annee;
    }

    /**
     * Vérifie que la session appartient bien à l'année active.
     */
    private function sessionAppartientAnneeActive(SessionPaiement $session): bool
    {
        $anneeActive = AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->first();

        if (!$anneeActive) {
            return false;
        }

        // La période doit appartenir à l'année active
        if ($session->type_periode === Paiement::MODE_MENSUEL) {
            return MoisScolaire::where('id', $session->periode)
                ->where('annee_scolaire_id', $anneeActive->id)
                ->exists();
        }

        return TrancheScolaire::where('id', $session->periode)
            ->where('annee_scolaire_id', $anneeActive->id)
            ->exists();
    }
}