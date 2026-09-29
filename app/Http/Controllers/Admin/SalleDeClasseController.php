<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalleDeClasseRequest;
use App\Http\Requests\UpdateSalleDeClasseRequest;
use App\Models\AnneeScolaire;
use App\Models\Devise;
use App\Models\MoisScolaire;
use App\Models\Option;
use App\Models\SalleDeClasse;
use App\Models\Section;
use App\Models\TrancheScolaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class SalleDeClasseController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const CACHE_TTL          = 3600;
    private const PER_PAGE           = 10;
    private const TAUX_CHANGE_DEFAUT = 2800.0;

    public const MODE_MENSUEL = 'mensuel';
    public const MODE_TRANCHE = 'tranche';

    private const CACHE_KEY_ANNEE_ACTIVE = 'annee_active_id';
    private const CACHE_KEY_TAUX_CHANGE  = 'taux_usd_cdf';

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', self::PER_PAGE);
        $perPage = max(5, min(100, $perPage));

        $query = SalleDeClasse::with(['section:id,nom', 'option:id,nom']);

        if ($search = $request->get('search')) {
            $query->where('nom', 'like', "%{$search}%");
        }

        if ($mode = $request->get('mode_paiement')) {
            if (in_array($mode, [self::MODE_MENSUEL, self::MODE_TRANCHE], true)) {
                $query->where('mode_paiement', $mode);
            }
        }

        $salles = $query->orderBy('nom')
            ->paginate($perPage)
            ->appends($request->except('page'));

        return view('admin.salles-de-classe.index', compact('salles'));
    }

    // ============================================================
    // CREATE / STORE
    // ============================================================

    public function create(): View
    {
        return view('admin.salles-de-classe.create', $this->getFormData());
    }

    public function store(StoreSalleDeClasseRequest $request): RedirectResponse
    {
        $anneeActive = $this->getAnneeActive();

        if ($anneeActive === null) {
            return back()
                ->with('error', "Aucune année scolaire active n'est définie. Veuillez d'abord créer une année scolaire.")
                ->withInput();
        }

        $data = $this->prepareSalleData($request->validated(), $anneeActive);

        try {
            $salle = DB::transaction(fn () => SalleDeClasse::create($data));

            Log::info('Salle de classe créée', [
                'salle_id'      => $salle->id,
                'nom'           => $data['nom'],
                'section_id'    => $data['section_id'],
                'mode_paiement' => $data['mode_paiement'],
                'user_id'       => auth()->id(),
            ]);

            return redirect()
                ->route('admin.salles-de-classe.index')
                ->with('success', "Salle « {$data['nom']} » créée avec succès.");

        } catch (Throwable $e) {
            Log::error('Erreur création salle', [
                'exception' => $e->getMessage(),
                'data'      => $data,
                'user_id'   => auth()->id(),
            ]);

            return back()
                ->with('error', 'Une erreur est survenue lors de la création. Veuillez réessayer.')
                ->withInput();
        }
    }

    // ============================================================
    // SHOW / EDIT / UPDATE
    // ============================================================

    public function show(SalleDeClasse $salleDeClasse): View
    {
        $salleDeClasse->load(['section:id,nom', 'option:id,nom']);

        return view('admin.salles-de-classe.show', compact('salleDeClasse'));
    }

    public function edit(SalleDeClasse $salleDeClasse): View
    {
        $data = $this->getFormData($salleDeClasse);
        $data['salleDeClasse'] = $salleDeClasse;

        return view('admin.salles-de-classe.edit', $data);
    }

    public function update(UpdateSalleDeClasseRequest $request, SalleDeClasse $salleDeClasse): RedirectResponse
    {
        $anneeActive = $this->getAnneeActive();

        if ($anneeActive === null) {
            return back()
                ->with('error', "Aucune année scolaire active n'est définie. Veuillez d'abord créer une année scolaire.")
                ->withInput();
        }

        $data = $this->prepareSalleData($request->validated(), $anneeActive);

        // Debug log pour tracer la modification
        Log::info('Données reçues pour mise à jour salle', [
            'salle_id' => $salleDeClasse->id,
            'data'     => $data,
        ]);

        try {
            DB::transaction(function () use ($salleDeClasse, $data) {
                $salleDeClasse->fill($data);

                // Ne sauvegarde que si quelque chose a changé
                if ($salleDeClasse->isDirty()) {
                    $salleDeClasse->save();
                }
            });

            Log::info('Salle de classe mise à jour', [
                'salle_id'      => $salleDeClasse->id,
                'nom'           => $data['nom'],
                'mode_paiement' => $data['mode_paiement'],
                'user_id'       => auth()->id(),
            ]);

            return redirect()
                ->route('admin.salles-de-classe.index')
                ->with('success', "Salle « {$data['nom']} » mise à jour avec succès.");

        } catch (Throwable $e) {
            Log::error('Erreur mise à jour salle #' . $salleDeClasse->id, [
                'exception' => $e->getMessage(),
                'data'      => $data,
                'user_id'   => auth()->id(),
            ]);

            return back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour. Veuillez réessayer.')
                ->withInput();
        }
    }

    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(SalleDeClasse $salleDeClasse): RedirectResponse
    {
        if (!$this->peutSupprimerSalle($salleDeClasse)) {
            return back()->with(
                'error',
                'Cette salle ne peut pas être supprimée car elle contient des élèves ou des paiements associés.'
            );
        }

        $nom = $salleDeClasse->nom;
        $id  = $salleDeClasse->id;

        try {
            DB::transaction(fn () => $salleDeClasse->delete());

            Log::info('Salle de classe supprimée', [
                'salle_id' => $id,
                'nom'      => $nom,
                'user_id'  => auth()->id(),
            ]);

            return redirect()
                ->route('admin.salles-de-classe.index')
                ->with('success', "Salle « {$nom} » supprimée avec succès.");

        } catch (Throwable $e) {
            Log::error('Erreur suppression salle #' . $id, [
                'exception' => $e->getMessage(),
                'user_id'   => auth()->id(),
            ]);

            return back()->with('error', 'Impossible de supprimer cette salle. Veuillez réessayer.');
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    private function getAnneeActive(): ?AnneeScolaire
    {
        $anneeId = Cache::remember(
            self::CACHE_KEY_ANNEE_ACTIVE,
            self::CACHE_TTL,
            fn () => AnneeScolaire::where('cloturee', false)
                ->latest('date_debut')
                ->value('id')
        );

        return $anneeId ? AnneeScolaire::find($anneeId) : null;
    }

    private function getNombreMoisActifs(AnneeScolaire $anneeActive): int
    {
        return MoisScolaire::where('annee_scolaire_id', $anneeActive->id)->count();
    }

    private function getNombreTranchesActifs(AnneeScolaire $anneeActive): int
    {
        return TrancheScolaire::where('annee_scolaire_id', $anneeActive->id)->count();
    }

    private function peutSupprimerSalle(SalleDeClasse $salle): bool
    {
        return !$salle->eleves()->exists()
            && !$salle->paiements()->exists();
    }

    private function getTauxChange(): float
    {
        return Cache::remember(self::CACHE_KEY_TAUX_CHANGE, self::CACHE_TTL, function () {
            $source = Devise::where('code', 'USD')->first();
            $cible  = Devise::where('code', 'CDF')->first();

            return ($source && $cible)
                ? (float) $source->tauxVers($cible)
                : self::TAUX_CHANGE_DEFAUT;
        });
    }

    private function getFormData(?SalleDeClasse $salle = null): array
    {
        $anneeActive = $this->getAnneeActive();

        $sallesQuery = SalleDeClasse::query()->orderBy('nom');

        if ($salle) {
            $sallesQuery->where('id', '!=', $salle->id);
        }

        return [
            'anneeActive'    => $anneeActive,
            'sections'       => Section::orderBy('nom')->get(['id', 'nom']),
            'options'        => Option::orderBy('nom')->get(['id', 'nom']),
            'salles'         => $sallesQuery->get(['id', 'nom']),
            'nombreMois'     => $anneeActive ? $this->getNombreMoisActifs($anneeActive) : 0,
            'nombreTranches' => $anneeActive ? $this->getNombreTranchesActifs($anneeActive) : 0,
            'tauxChange'     => $this->getTauxChange(),
        ];
    }

    private function prepareSalleData(array $data, ?AnneeScolaire $anneeActive): array
    {
        if ($data['mode_paiement'] === self::MODE_MENSUEL) {
            if (empty($data['frais_scolarite_mensuel']) && $anneeActive && !empty($data['frais_annuel'])) {
                $nombreMois = $this->getNombreMoisActifs($anneeActive);
                if ($nombreMois > 0) {
                    $data['frais_scolarite_mensuel'] = round((float) $data['frais_annuel'] / $nombreMois, 2);
                }
            }
            $data['frais_par_tranche'] = null;
            $data['nombre_tranches']   = null;

        } elseif ($data['mode_paiement'] === self::MODE_TRANCHE) {
            if ($anneeActive && empty($data['nombre_tranches'])) {
                $data['nombre_tranches'] = $this->getNombreTranchesActifs($anneeActive);
            }
            if (empty($data['frais_par_tranche']) && $anneeActive && !empty($data['frais_annuel'])) {
                $nombreTranches = (int) ($data['nombre_tranches'] ?? $this->getNombreTranchesActifs($anneeActive));
                if ($nombreTranches > 0) {
                    $data['frais_par_tranche'] = round((float) $data['frais_annuel'] / $nombreTranches, 2);
                }
            }
            $data['frais_scolarite_mensuel'] = null;
        }

        return $data;
    }
}