<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\PeriodeNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeriodeNoteController extends Controller
{
    // ============================================================
    // LISTE
    // ============================================================

    public function index(Request $request): View
    {
        $anneeId   = $request->integer('annee_scolaire_id') ?: null;
        $estActive = $request->filled('est_active')
            ? $request->boolean('est_active')
            : null;
        $search    = trim((string) $request->input('search', ''));

        $periodes = PeriodeNote::query()
            ->with('anneeScolaire')
            ->withCount('notes')
            ->when($anneeId, fn ($q) => $q->pourAnnee($anneeId))
            ->when($estActive !== null, fn ($q) => $q->where('est_active', $estActive))
            ->when($search !== '', fn ($q) => $q->recherche($search))
            ->chronologique()
            ->paginate(15)
            ->withQueryString();

        // ✅ Statistiques en 1 seule requête SQL (au lieu de 4)
        $stats = $this->getStatistiques();

        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('admin.periode-notes.index', [
            'periodes'       => $periodes,
            'annees'         => $annees,
            'anneeId'        => $anneeId,
            'estActive'      => $estActive,
            'search'         => $search,
            'totalPeriodes'  => $stats['total'],
            'totalActives'   => $stats['actives'],
            'totalInactives' => $stats['inactives'],
            'totalEnCours'   => $stats['en_cours'],
        ]);
    }

    // ============================================================
    // CRÉATION
    // ============================================================

    public function create(): View
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('admin.periode-notes.create', compact('annees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePeriode($request);

        try {
            // ✅ Transaction : si la création échoue, les autres périodes restent intactes
            $periode = DB::transaction(function () use ($data) {
                if (!empty($data['est_active'])) {
                    $this->desactiverAutresPeriodes();
                }

                return PeriodeNote::create($data);
            });

            Log::debug('Période de notation créée', [
                'periode_id' => $periode->id,
                'nom'        => $periode->nom,
                'user_id'    => auth()->id(),
            ]);

            return redirect()
                ->route('admin.periode-notes.index')
                ->with('success', 'Période de notation créée avec succès.');

        } catch (\Throwable $e) {
            Log::error('Erreur création période', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création.');
        }
    }

    // ============================================================
    // DÉTAIL
    // ============================================================

    public function show(PeriodeNote $periodeNote): View
    {
        $periodeNote->load('anneeScolaire')->loadCount('notes');

        return view('admin.periode-notes.show', compact('periodeNote'));
    }

    // ============================================================
    // ÉDITION
    // ============================================================

    public function edit(PeriodeNote $periodeNote): View
    {
        $annees = AnneeScolaire::orderByDesc('date_debut')->get();

        return view('admin.periode-notes.edit', compact('periodeNote', 'annees'));
    }

    public function update(Request $request, PeriodeNote $periodeNote): RedirectResponse
    {
        $data = $this->validatePeriode($request, $periodeNote->id);

        try {
            DB::transaction(function () use ($periodeNote, $data) {
                // Si on active ET que la période n'était pas active → désactiver les autres
                if (!empty($data['est_active']) && !$periodeNote->est_active) {
                    $this->desactiverAutresPeriodes();
                }

                $periodeNote->update($data);
            });

            Log::debug('Période de notation mise à jour', [
                'periode_id' => $periodeNote->id,
                'nom'        => $periodeNote->nom,
                'user_id'    => auth()->id(),
            ]);

            return redirect()
                ->route('admin.periode-notes.index')
                ->with('success', 'Période de notation mise à jour.');

        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour période', [
                'periode_id' => $periodeNote->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour.');
        }
    }

    // ============================================================
    // SUPPRESSION
    // ============================================================

    public function destroy(PeriodeNote $periodeNote): RedirectResponse
    {
        if ($periodeNote->notes()->exists()) {
            return back()->with('error', 'Cette période contient déjà des notes. Impossible de la supprimer.');
        }

        try {
            DB::transaction(fn () => $periodeNote->delete());

            Log::debug('Période de notation supprimée', [
                'periode_id' => $periodeNote->id,
                'nom'        => $periodeNote->nom,
                'user_id'    => auth()->id(),
            ]);

            return redirect()
                ->route('admin.periode-notes.index')
                ->with('success', 'Période de notation supprimée.');

        } catch (\Throwable $e) {
            Log::error('Erreur suppression période', [
                'periode_id' => $periodeNote->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Impossible de supprimer cette période. Veuillez réessayer.');
        }
    }

    // ============================================================
    // TOGGLE (ACTIVER / DÉSACTIVER)
    // ============================================================

    public function toggle(PeriodeNote $periodeNote): RedirectResponse
    {
        $nouvelEtat = !$periodeNote->est_active;

        try {
            DB::transaction(function () use ($periodeNote, $nouvelEtat) {
                if ($nouvelEtat) {
                    $this->desactiverAutresPeriodes();
                }

                $periodeNote->update(['est_active' => $nouvelEtat]);
            });

            $etat = $nouvelEtat ? 'activée' : 'désactivée';

            Log::debug("Période de notation {$etat}", [
                'periode_id' => $periodeNote->id,
                'nom'        => $periodeNote->nom,
                'user_id'    => auth()->id(),
            ]);

            return back()->with('success', "La période a été {$etat} avec succès.");

        } catch (\Throwable $e) {
            Log::error('Erreur toggle période', [
                'periode_id' => $periodeNote->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue. Veuillez réessayer.');
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Désactive toutes les périodes actives.
     */
    private function desactiverAutresPeriodes(): void
    {
        PeriodeNote::active()->update(['est_active' => false]);
    }

    /**
     * Statistiques globales en 1 seule requête SQL.
     *
     * @return array{total:int, actives:int, inactives:int, en_cours:int}
     */
    private function getStatistiques(): array
    {
        $row = PeriodeNote::query()
            ->selectRaw('
                COUNT(*) AS total,
                SUM(CASE WHEN est_active = 1 THEN 1 ELSE 0 END) AS actives,
                SUM(CASE WHEN est_active = 0 THEN 1 ELSE 0 END) AS inactives,
                SUM(CASE WHEN est_active = 1
                         AND date_debut <= CURDATE()
                         AND date_fin >= CURDATE() THEN 1 ELSE 0 END) AS en_cours
            ')
            ->first();

        return [
            'total'     => (int) ($row->total ?? 0),
            'actives'   => (int) ($row->actives ?? 0),
            'inactives' => (int) ($row->inactives ?? 0),
            'en_cours'  => (int) ($row->en_cours ?? 0),
        ];
    }

    /**
     * Validation commune (store + update).
     *
     * ✅ Unicité du `nom` scopée par année scolaire :
     *    "Trimestre 1" peut exister en 2025 ET en 2026.
     */
    private function validatePeriode(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'annee_scolaire_id' => ['nullable', 'exists:annees_scolaires,id'],
            'nom'               => [
                'required',
                'string',
                'max:255',
                Rule::unique('periode_notes', 'nom')
                    ->where(fn ($q) => $q->where('annee_scolaire_id', $request->input('annee_scolaire_id')))
                    ->ignore($ignoreId),
            ],
            'date_debut'   => ['nullable', 'date'],
            'date_fin'     => ['nullable', 'date', 'after_or_equal:date_debut'],
            'est_active'   => ['sometimes', 'boolean'],
            'description'  => ['nullable', 'string', 'max:500'],
        ], [
            'nom.required'           => 'Le nom de la période est obligatoire.',
            'nom.unique'             => 'Ce nom de période existe déjà pour cette année scolaire.',
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]);
    }
}