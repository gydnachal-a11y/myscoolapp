<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\TrancheScolaire;
use App\Services\PeriodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrancheScolaireController extends Controller
{
    public function __construct(
        protected PeriodeService $periodeService
    ) {}

    // ============================================================
    // LISTE
    // ============================================================

    public function index(Request $request): View
    {
        $anneeId = $request->integer('annee_scolaire_id') ?: null;

        // ✅ Pas de cache : table de 5-10 lignes, requête instantanée et fiable
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        $tranches = TrancheScolaire::query()
            ->with('anneeScolaire')
            ->when($anneeId, fn ($q) => $q->deAnnee($anneeId))
            ->chronologique()
            ->paginate(15)
            ->withQueryString();  // ✅ préserve les filtres dans la pagination

        return view('admin.tranches-scolaires.index', compact('tranches', 'annees', 'anneeId'));
    }

    // ============================================================
    // CRÉATION
    // ============================================================

    public function create(): View
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('admin.tranches-scolaires.create', compact('annees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTranche($request);

        try {
            $tranche = DB::transaction(fn () => TrancheScolaire::create($data));

            Log::info('Tranche scolaire créée', [
                'tranche_id' => $tranche->id,
                'tranche'    => $tranche->tranche,
                'annee_id'   => $tranche->annee_scolaire_id,
            ]);

            return redirect()
                ->route('admin.tranches-scolaires.index')
                ->with('success', 'Tranche ajoutée avec succès.');

        } catch (\Throwable $e) {
            Log::error("Erreur lors de la création d'une tranche scolaire", [
                'exception' => $e->getMessage(),
                'data'      => $data,
            ]);

            return back()->withInput()->with('error', "Une erreur est survenue lors de l'enregistrement.");
        }
    }

    // ============================================================
    // DÉTAIL
    // ============================================================

    public function show(TrancheScolaire $trancheScolaire): View
    {
        $trancheScolaire->load('anneeScolaire');

        return view('admin.tranches-scolaires.show', compact('trancheScolaire'));
    }

    // ============================================================
    // ÉDITION
    // ============================================================

    public function edit(TrancheScolaire $trancheScolaire): View
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('admin.tranches-scolaires.edit', compact('trancheScolaire', 'annees'));
    }

    public function update(Request $request, TrancheScolaire $trancheScolaire): RedirectResponse
    {
        $data = $this->validateTranche($request, $trancheScolaire->id);

        try {
            DB::transaction(fn () => $trancheScolaire->update($data));

            Log::info('Tranche scolaire mise à jour', [
                'tranche_id' => $trancheScolaire->id,
                'data'       => $data,
            ]);

            return redirect()
                ->route('admin.tranches-scolaires.index')
                ->with('success', 'Tranche mise à jour avec succès.');

        } catch (\Throwable $e) {
            Log::error("Erreur lors de la mise à jour de la tranche #{$trancheScolaire->id}", [
                'exception' => $e->getMessage(),
                'data'      => $data,
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour.');
        }
    }

    // ============================================================
    // SUPPRESSION
    // ============================================================

    public function destroy(TrancheScolaire $trancheScolaire): RedirectResponse
    {
        try {
            DB::transaction(fn () => $trancheScolaire->delete());

            return redirect()
                ->route('admin.tranches-scolaires.index')
                ->with('success', 'Tranche supprimée avec succès.');

        } catch (\Throwable $e) {
            Log::error("Erreur lors de la suppression de la tranche #{$trancheScolaire->id}", [
                'exception' => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible de supprimer cette tranche. Veuillez réessayer.');
        }
    }

    // ============================================================
    // ACTIONS SPÉCIALES
    // ============================================================

    /**
     * Génère automatiquement les tranches pour l'année scolaire active.
     */
    public function generer(Request $request): RedirectResponse
    {
        $anneeActive = $this->getAnneeActive();

        if (!$anneeActive) {
            return back()->with('error', 'Aucune année scolaire active.');
        }

        // ✅ Confirmation si des tranches existent déjà
        $existant = TrancheScolaire::deAnnee($anneeActive->id)->exists();
        if ($existant && !$request->boolean('force')) {
            return back()->with(
                'warning',
                "Des tranches existent déjà pour « {$anneeActive->libelle} ». Confirmez la régénération pour les remplacer."
            );
        }

        try {
            DB::transaction(function () use ($anneeActive) {
                // On supprime proprement via Eloquent (pas truncate) pour respecter les FK
                TrancheScolaire::deAnnee($anneeActive->id)->delete();
                $this->periodeService->genererTranches($anneeActive);
            });

            Log::info('Tranches scolaires générées', [
                'annee_id' => $anneeActive->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.tranches-scolaires.index')
                ->with('success', 'Tranches générées automatiquement avec succès.');

        } catch (\Throwable $e) {
            Log::error('Erreur lors de la génération des tranches', [
                'exception'       => $e->getMessage(),
                'annee_active_id' => $anneeActive->id,
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la génération.');
        }
    }

    /**
     * Vide les tranches (année active par défaut, ou toutes).
     */
    public function vider(Request $request): RedirectResponse
    {
        $anneeId = $request->integer('annee_scolaire_id') ?: null;

        try {
            DB::transaction(function () use ($anneeId) {
                if ($anneeId) {
                    TrancheScolaire::where('annee_scolaire_id', $anneeId)->delete();
                } else {
                    // ⚠️ delete() au lieu de truncate() :
                    //  - truncate() ne peut pas être rollbacké
                    //  - truncate() ignore les FK et peut casser l'intégrité
                    TrancheScolaire::query()->delete();
                }
            });

            $message = $anneeId
                ? 'Tranches de cette année supprimées.'
                : 'Toutes les tranches scolaires ont été supprimées.';

            return redirect()
                ->route('admin.tranches-scolaires.index')
                ->with('success', $message);

        } catch (\Throwable $e) {
            Log::error('Erreur lors du vidage de la table tranches_scolaires', [
                'exception' => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Validation partagée (store + update).
     *
     * ⚠️ L'unicité de `tranche` est scopée par `annee_scolaire_id`
     *    → "1ère tranche" autorisée dans plusieurs années.
     */
    private function validateTranche(Request $request, ?int $trancheId = null): array
    {
        return $request->validate([
            'annee_scolaire_id' => ['required', 'exists:annees_scolaires,id'],
            'tranche'           => [
                'required', 'string', 'max:50',
                Rule::unique('tranches_scolaires', 'tranche')
                    ->where(fn ($q) => $q->where('annee_scolaire_id', $request->input('annee_scolaire_id')))
                    ->ignore($trancheId),
            ],
            'date_debut'        => ['required', 'date'],
            'date_fin'          => ['required', 'date', 'after_or_equal:date_debut'],
        ], [
            'annee_scolaire_id.required' => "L'année scolaire est obligatoire.",
            'annee_scolaire_id.exists'   => "L'année scolaire sélectionnée n'existe pas.",
            'tranche.required'           => 'Le nom de la tranche est obligatoire.',
            'tranche.unique'             => 'Ce nom de tranche existe déjà pour cette année.',
            'date_debut.required'        => 'La date de début est obligatoire.',
            'date_fin.required'          => 'La date de fin est obligatoire.',
            'date_fin.after_or_equal'    => 'La date de fin doit être égale ou postérieure à la date de début.',
        ]);
    }

    /**
     * Récupère l'année scolaire active.
     */
    private function getAnneeActive(): ?AnneeScolaire
    {
        return AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->first();
    }
}