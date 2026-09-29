<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\MoisScolaire;
use App\Services\PeriodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MoisScolaireController extends Controller
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

        $mois = MoisScolaire::query()
            ->with('anneeScolaire')
            ->when($anneeId, fn ($q) => $q->deAnnee($anneeId))
            ->chronologique()
            ->paginate(15)
            ->withQueryString();

        return view('admin.mois-scolaires.index', compact('mois', 'annees', 'anneeId'));
    }

    // ============================================================
    // CRÉATION
    // ============================================================

    public function create(): View
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('admin.mois-scolaires.create', compact('annees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        try {
            MoisScolaire::create($data);

            return redirect()
                ->route('admin.mois-scolaires.index')
                ->with('success', 'Mois scolaire ajouté avec succès.');

        } catch (\Throwable $e) {
            Log::error('Erreur création mois scolaire', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Une erreur est survenue.');
        }
    }

    // ============================================================
    // DÉTAIL
    // ============================================================

    public function show(MoisScolaire $moisScolaire): View
    {
        $moisScolaire->load(['anneeScolaire', 'paiementSalaires']);

        return view('admin.mois-scolaires.show', compact('moisScolaire'));
    }

    // ============================================================
    // ÉDITION
    // ============================================================

    public function edit(MoisScolaire $moisScolaire): View
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('admin.mois-scolaires.edit', compact('moisScolaire', 'annees'));
    }

    public function update(Request $request, MoisScolaire $moisScolaire): RedirectResponse
    {
        $data = $this->validateData($request, $moisScolaire->id);

        try {
            $moisScolaire->update($data);

            return redirect()
                ->route('admin.mois-scolaires.index')
                ->with('success', 'Mois scolaire mis à jour.');

        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour mois scolaire', [
                'id'    => $moisScolaire->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue.');
        }
    }

    // ============================================================
    // SUPPRESSION
    // ============================================================

    public function destroy(MoisScolaire $moisScolaire): RedirectResponse
    {
        $moisScolaire->delete();

        return redirect()
            ->route('admin.mois-scolaires.index')
            ->with('success', 'Mois scolaire supprimé.');
    }

    // ============================================================
    // ACTIONS SPÉCIALES
    // ============================================================

    /**
     * Génère automatiquement les mois de l'année scolaire active.
     */
    public function generer(): RedirectResponse
    {
        $anneeActive = $this->getAnneeActive();

        if (!$anneeActive) {
            return back()->with('error', 'Aucune année scolaire active trouvée.');
        }

        // Empêche la double génération
        if (MoisScolaire::deAnnee($anneeActive->id)->exists()) {
            return back()->with('info', "Les mois de l'année « {$anneeActive->libelle} » existent déjà.");
        }

        try {
            $this->periodeService->genererMois($anneeActive);

            return redirect()
                ->route('admin.mois-scolaires.index')
                ->with('success', "Mois générés pour l'année « {$anneeActive->libelle} ».");

        } catch (\Throwable $e) {
            Log::error('Erreur génération mois scolaires', [
                'annee_id' => $anneeActive->id,
                'error'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la génération.');
        }
    }

    /**
     * Vide les mois scolaires (année active par défaut, ou tout).
     */
    public function vider(Request $request): RedirectResponse
    {
        $anneeId = $request->integer('annee_scolaire_id') ?: null;

        try {
            DB::transaction(function () use ($anneeId) {
                if ($anneeId) {
                    MoisScolaire::where('annee_scolaire_id', $anneeId)->delete();
                } else {
                    // ⚠️ delete() au lieu de truncate() :
                    //  - truncate ne peut pas être rollbacké
                    //  - truncate ignore les FK et peut casser l'intégrité
                    MoisScolaire::query()->delete();
                }
            });

            $message = $anneeId
                ? 'Mois scolaires de cette année supprimés.'
                : 'Tous les mois scolaires ont été supprimés.';

            return back()->with('success', $message);

        } catch (\Throwable $e) {
            Log::error('Erreur vidage mois scolaires', [
                'annee_id' => $anneeId,
                'error'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Règles de validation partagées (store + update).
     */
    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'annee_scolaire_id' => ['required', 'exists:annees_scolaires,id'],
            'mois'              => [
                'required', 'string', 'max:10',
                // ✅ Unicité scopée : même mois interdit dans la MÊME année seulement
                Rule::unique('mois_scolaires', 'mois')
                    ->where(fn ($q) => $q->where('annee_scolaire_id', $request->input('annee_scolaire_id')))
                    ->ignore($ignoreId),
            ],
            'nom_mois'   => ['nullable', 'string', 'max:50'],
            'date_debut' => ['required', 'date'],
            'date_fin'   => ['required', 'date', 'after_or_equal:date_debut'],
        ], [
            'annee_scolaire_id.required' => "L'année scolaire est obligatoire.",
            'annee_scolaire_id.exists'   => "L'année scolaire sélectionnée n'existe pas.",
            'mois.required'              => 'Le code du mois est obligatoire.',
            'mois.unique'                => 'Ce mois existe déjà pour cette année scolaire.',
            'date_debut.required'        => 'La date de début est obligatoire.',
            'date_fin.required'          => 'La date de fin est obligatoire.',
            'date_fin.after_or_equal'    => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);
    }

    /**
     * Récupère l'année scolaire active (non clôturée).
     */
    private function getAnneeActive(): ?AnneeScolaire
    {
        return AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->first();
    }
}