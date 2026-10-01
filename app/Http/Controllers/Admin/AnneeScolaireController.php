<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnneeScolaireRequest;
use App\Http\Requests\UpdateAnneeScolaireRequest;
use App\Models\AnneeScolaire;
use App\Models\Note;
use App\Models\Paiement;
use App\Services\PeriodeService;
use App\Services\TransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AnneeScolaireController extends Controller
{
    public function __construct(
        protected PeriodeService $periodeService,
        protected TransitionService $transitionService
    ) {}

    // ============================================================
    // LISTE
    // ============================================================

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $statut = $request->input('statut'); // en_cours / cloturee / a_venir / passee

        $annees = AnneeScolaire::query()
            ->withCount('inscriptions')
            ->when($search !== '', fn ($q) => $q->search($search))
            ->when($statut, function ($q) use ($statut) {
                match ($statut) {
                    'en_cours' => $q->enCours(),
                    'cloturee' => $q->cloturees(),
                    'a_venir'  => $q->futures(),
                    'passee'   => $q->passees(),
                    default    => null,
                };
            })
            ->orderByDesc('date_debut')
            ->paginate(10)
            ->withQueryString();

        return view('admin.annees-scolaires.index', compact('annees', 'search', 'statut'));
    }

    /**
     * Corbeille : années soft-deleted.
     */
    public function corbeille(): View
    {
        $anneesSupprimees = AnneeScolaire::onlyTrashed()
            ->withCount('inscriptions')
            ->orderByDesc('date_debut')
            ->paginate(10);

        return view('admin.annees-scolaires.corbeille', compact('anneesSupprimees'));
    }

    // ============================================================
    // RESTAURATION & SUPPRESSION DÉFINITIVE
    // ============================================================

    /**
     * Restaure une année scolaire supprimée.
     */
    public function restaurer(int $id): RedirectResponse
    {
        $annee = AnneeScolaire::onlyTrashed()->findOrFail($id);

        // ✅ Vérifie qu'aucune autre année active n'a le même libellé
        $conflit = AnneeScolaire::where('libelle', $annee->libelle)
            ->where('id', '!=', $annee->id)
            ->exists();

        if ($conflit) {
            return back()->with(
                'error',
                "Impossible de restaurer : une année avec le libellé « {$annee->libelle} » existe déjà."
            );
        }

        try {
            $annee->restore();

            Log::info('Année scolaire restaurée', [
                'annee_id' => $annee->id,
                'libelle'  => $annee->libelle,
                'admin_id' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.annees-scolaires.corbeille')
                ->with('success', 'Année scolaire restaurée avec succès.');

        } catch (Throwable $e) {
            Log::error('Erreur restauration année', [
                'annee_id' => $id,
                'error'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la restauration.');
        }
    }

    /**
     * Supprime définitivement une année de la corbeille.
     *
     * ✅ Vérifie qu'il n'y a AUCUNE dépendance active avant de supprimer.
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $annee = AnneeScolaire::onlyTrashed()->findOrFail($id);

        // Garde-fous : refuse la suppression si des données sont liées
        $dependances = $this->compterDependances($annee);

        if ($dependances['inscriptions'] > 0 || $dependances['notes'] > 0 || $dependances['paiements'] > 0) {
            Log::warning('Tentative forceDelete bloquée', [
                'annee_id'    => $annee->id,
                'dependances' => $dependances,
                'admin_id'    => auth()->id(),
            ]);

            return back()->with(
                'error',
                "Suppression impossible : cette année contient encore "
                . $dependances['inscriptions'] . " inscription(s), "
                . $dependances['paiements'] . " paiement(s) et "
                . $dependances['notes'] . " note(s). "
                . "Restaurez-la puis nettoyez manuellement, ou conservez-la en corbeille."
            );
        }

        try {
            DB::transaction(function () use ($annee) {
                // Supprime en cascade les dépendances légères
                $annee->moisScolaires()->delete();
                $annee->tranchesScolaires()->delete();
                $annee->periodeNotes()->delete();

                $annee->forceDelete();
            });

            Log::info('Année scolaire supprimée définitivement', [
                'annee_id' => $annee->id,
                'libelle'  => $annee->libelle,
                'admin_id' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.annees-scolaires.corbeille')
                ->with('success', 'Année scolaire définitivement supprimée.');

        } catch (Throwable $e) {
            Log::error('Erreur forceDelete année', [
                'annee_id' => $id,
                'error'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la suppression définitive.');
        }
    }

    // ============================================================
    // CRÉATION
    // ============================================================

    public function create(): View
    {
        return view('admin.annees-scolaires.create');
    }

    public function store(StoreAnneeScolaireRequest $request): RedirectResponse
    {
        // ✅ Garde-fou : une seule année non clôturée à la fois
        if (AnneeScolaire::ouvertes()->exists()) {
            return back()->withInput()->with(
                'error',
                "Une année scolaire est déjà ouverte. Clôturez-la avant d'en créer une nouvelle."
            );
        }

        try {
            $annee = DB::transaction(function () use ($request) {
                $data = $request->validated();
                $data['paiement_ouvert'] = $request->boolean('paiement_ouvert');

                $annee = AnneeScolaire::create($data);
                $this->periodeService->genererPourAnnee($annee);

                return $annee;
            });

            Log::info('Année scolaire créée', [
                'annee_id' => $annee->id,
                'libelle'  => $annee->libelle,
                'admin_id' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.annees-scolaires.index')
                ->with('success', "Année « {$annee->libelle} » créée et périodes générées.");

        } catch (Throwable $e) {
            Log::error('Erreur création année scolaire', [
                'error' => $e->getMessage(),
                'data'  => $request->validated(),
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création.');
        }
    }

    // ============================================================
    // ÉDITION
    // ============================================================

    public function edit(AnneeScolaire $anneeScolaire): View
    {
        return view('admin.annees-scolaires.edit', compact('anneeScolaire'));
    }

    public function update(UpdateAnneeScolaireRequest $request, AnneeScolaire $anneeScolaire): RedirectResponse
    {
        if ($anneeScolaire->cloturee) {
            return back()->with('error', 'Impossible de modifier une année clôturée.');
        }

        try {
            DB::transaction(function () use ($request, $anneeScolaire) {
                $data = $request->validated();
                $data['paiement_ouvert'] = $request->boolean('paiement_ouvert');

                // ✅ Comparaison stricte sur des chaînes de date
                $anciennes = [
                    'date_debut'      => $anneeScolaire->date_debut?->toDateString(),
                    'date_fin'        => $anneeScolaire->date_fin?->toDateString(),
                    'nombre_mois'     => $anneeScolaire->nombre_mois,
                    'nombre_tranches' => $anneeScolaire->nombre_tranches,
                ];

                $anneeScolaire->update($data);
                $anneeScolaire->refresh();

                $nouvelles = [
                    'date_debut'      => $anneeScolaire->date_debut?->toDateString(),
                    'date_fin'        => $anneeScolaire->date_fin?->toDateString(),
                    'nombre_mois'     => $anneeScolaire->nombre_mois,
                    'nombre_tranches' => $anneeScolaire->nombre_tranches,
                ];

                if ($anciennes !== $nouvelles) {
                    $this->periodeService->genererPourAnnee($anneeScolaire);
                }
            });

            Log::info('Année scolaire mise à jour', [
                'annee_id' => $anneeScolaire->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.annees-scolaires.index')
                ->with('success', 'Année scolaire mise à jour.');

        } catch (Throwable $e) {
            Log::error('Erreur mise à jour année scolaire', [
                'annee_id' => $anneeScolaire->id,
                'error'    => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour.');
        }
    }

    // ============================================================
    // SUPPRESSION (SOFT DELETE)
    // ============================================================

    public function destroy(AnneeScolaire $anneeScolaire): RedirectResponse
    {
        if ($anneeScolaire->cloturee) {
            return back()->with('error', 'Impossible de supprimer une année clôturée.');
        }

        $nbInscriptions = $anneeScolaire->inscriptions()->count();
        if ($nbInscriptions > 0) {
            return back()->with(
                'error',
                "Impossible de supprimer : cette année contient {$nbInscriptions} inscription(s)."
            );
        }

        try {
            $anneeScolaire->delete();

            Log::info('Année scolaire mise en corbeille', [
                'annee_id' => $anneeScolaire->id,
                'libelle'  => $anneeScolaire->libelle,
                'admin_id' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.annees-scolaires.index')
                ->with('success', 'Année scolaire déplacée dans la corbeille.');

        } catch (Throwable $e) {
            Log::error('Erreur suppression année', [
                'annee_id' => $anneeScolaire->id,
                'error'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la suppression.');
        }
    }

    // ============================================================
    // TOGGLES (CLÔTURE / PAIEMENT)
    // ============================================================

    public function toggleCloture(AnneeScolaire $anneeScolaire): RedirectResponse
    {
        $vaCloturer = ! $anneeScolaire->cloturee;

        if (! $vaCloturer) {
            $dependances = $this->compterDependances($anneeScolaire);

            Log::warning('Réouverture d\'une année clôturée', [
                'annee_id'    => $anneeScolaire->id,
                'libelle'     => $anneeScolaire->libelle,
                'dependances' => $dependances,
                'admin_id'    => auth()->id(),
            ]);
        }

        try {
            DB::transaction(function () use ($anneeScolaire, $vaCloturer) {
                $anneeScolaire->update(['cloturee' => $vaCloturer]);
            });

            $etat = $vaCloturer ? 'clôturée' : 'réouverte';

            Log::info("Année scolaire {$etat}", [
                'annee_id' => $anneeScolaire->id,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('success', "Année scolaire {$etat}.");

        } catch (Throwable $e) {
            Log::error('Erreur toggle clôture', [
                'annee_id' => $anneeScolaire->id,
                'error'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue.');
        }
    }

    public function togglePaiement(AnneeScolaire $anneeScolaire): RedirectResponse
    {
        if ($anneeScolaire->cloturee) {
            return back()->with('error', 'Année clôturée, impossible de changer le statut des paiements.');
        }

        try {
            DB::transaction(function () use ($anneeScolaire) {
                $anneeScolaire->update(['paiement_ouvert' => ! $anneeScolaire->paiement_ouvert]);
            });

            $etat = $anneeScolaire->refresh()->paiement_ouvert ? 'ouverts' : 'fermés';

            return back()->with('success', "Paiements {$etat}.");

        } catch (Throwable $e) {
            Log::error('Erreur toggle paiement', [
                'annee_id' => $anneeScolaire->id,
                'error'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue.');
        }
    }

    // ============================================================
    // RÉGÉNÉRATION DES PÉRIODES
    // ============================================================

    public function regenererPeriodes(AnneeScolaire $anneeScolaire): RedirectResponse
    {
        if ($anneeScolaire->cloturee) {
            return back()->with('error', 'Année clôturée, régénération impossible.');
        }

        $nbNotes = Note::whereHas('periodeNote', fn ($q) => $q->where('annee_scolaire_id', $anneeScolaire->id))->count();

        if ($nbNotes > 0) {
            return back()->with(
                'error',
                "Régénération impossible : {$nbNotes} note(s) sont déjà liées à des périodes de cette année."
            );
        }

        try {
            DB::transaction(fn () => $this->periodeService->genererPourAnnee($anneeScolaire));

            Log::info('Périodes régénérées', [
                'annee_id' => $anneeScolaire->id,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('success', 'Périodes régénérées avec succès.');

        } catch (Throwable $e) {
            Log::error('Erreur régénération périodes', [
                'annee_id' => $anneeScolaire->id,
                'error'    => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la régénération.');
        }
    }

    // ============================================================
    // CLÔTURE & TRANSITION
    // ============================================================

    public function cloturer(AnneeScolaire $anneeScolaire): View|RedirectResponse
    {
        if ($anneeScolaire->cloturee) {
            return redirect()
                ->route('admin.annees-scolaires.index')
                ->with('error', 'Cette année est déjà clôturée.');
        }

        $anneeSuivante = AnneeScolaire::where('date_debut', '>', $anneeScolaire->date_fin)
            ->orderBy('date_debut')
            ->first();

        return view('admin.annees-scolaires.cloturer', compact('anneeScolaire', 'anneeSuivante'));
    }

    public function appliquerTransition(Request $request, AnneeScolaire $anneeScolaire): RedirectResponse
    {
        $data = $request->validate([
            'nouvelle_annee_id'    => ['nullable', 'exists:annees_scolaires,id', 'different:' . $anneeScolaire->id],
            'creer_nouvelle_annee' => ['nullable', 'boolean'],
            'transferer_eleves'    => ['nullable', 'boolean'],
            'transferer_config'    => ['nullable', 'boolean'],
            'transferer_cours'     => ['nullable', 'boolean'],
            'transferer_personnel' => ['nullable', 'boolean'],
            'reset_complet'        => ['nullable', 'boolean'],
        ]);

        $actions = array_filter([
            $data['transferer_eleves']    ?? false,
            $data['transferer_config']    ?? false,
            $data['transferer_cours']     ?? false,
            $data['transferer_personnel'] ?? false,
            $data['reset_complet']        ?? false,
        ]);

        if (empty($actions)) {
            return back()->with('error', 'Veuillez choisir au moins une option de transfert ou de réinitialisation.');
        }

        if (($data['creer_nouvelle_annee'] ?? false) && ! empty($data['nouvelle_annee_id'])) {
            return back()->with('error', 'Choisissez soit de créer une nouvelle année, soit d\'en sélectionner une existante.');
        }

        try {
            $result = $this->transitionService->executer($anneeScolaire, $data);

            if (! empty($result['success'])) {
                Log::info('Transition d\'année effectuée', [
                    'source_id' => $anneeScolaire->id,
                    'options'   => $actions,
                    'admin_id'  => auth()->id(),
                ]);

                return redirect()
                    ->route('admin.annees-scolaires.index')
                    ->with('success', $result['message']);
            }

            Log::warning('Transition échouée', [
                'source_id' => $anneeScolaire->id,
                'message'   => $result['message'] ?? 'Inconnu',
            ]);

            return back()->with('error', $result['message'] ?? 'Transition impossible.');

        } catch (Throwable $e) {
            Log::error('Erreur transition', [
                'source_id' => $anneeScolaire->id,
                'error'     => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la transition.');
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Compte les dépendances critiques d'une année scolaire.
     *
     * @return array{inscriptions:int, notes:int, paiements:int}
     */
    private function compterDependances(AnneeScolaire $annee): array
    {
        return [
            'inscriptions' => $annee->inscriptions()->count(),

            'notes' => Note::whereHas(
                'periodeNote',
                fn ($q) => $q->where('annee_scolaire_id', $annee->id)
            )->count(),

            // ✅ CORRECTION : pas de relation `inscription()` sur Paiement.
            //    On filtre directement sur `annee_scolaire_id`.
            'paiements' => Paiement::where('annee_scolaire_id', $annee->id)->count(),
        ];
    }
}