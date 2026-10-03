<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvanceSalaire;
use App\Models\MoisScolaire;
use App\Models\User;
use App\Services\AvanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class AvanceSalaireController extends Controller
{
    public function __construct(
        protected AvanceService $avanceService,
    ) {}

    // ============================================================
    // INDEX — Liste des avances
    // ============================================================

    public function index(Request $request): View
    {
        $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'statut'  => 'nullable|in:' . implode(',', AvanceSalaire::STATUTS),
        ]);

        $avances = AvanceSalaire::with(['user', 'moisScolaire'])
            ->when($request->filled('user_id'), fn ($q) => $q->forUser((int) $request->user_id))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $search = trim((string) $request->input('recherche'));
                $q->whereHas('user', function ($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('date_avance')
            ->paginate(20)
            ->appends($request->query());

        // ✅ Charge tous les users complets (avec type_salaire + salaire_*)
        $users = User::orderBy('name')->get();
        $mois  = MoisScolaire::orderBy('mois')->get();

        $stats = [
            'total'             => AvanceSalaire::count(),
            'actives'           => AvanceSalaire::actives()->count(),
            'remboursees'       => AvanceSalaire::remboursees()->count(),
            'montant_total_usd' => (float) AvanceSalaire::sum('montant_avance_usd'),
            'dette_totale_usd'  => (float) AvanceSalaire::actives()->sum('dette_restante_usd'),
        ];

        $dettesParUser = collect($this->avanceService->getUsersData($users))
            ->keyBy('id')
            ->toArray();

        return view('admin.avances.index', compact(
            'avances',
            'users',
            'mois',
            'dettesParUser',
            'stats'
        ));
    }

    // ============================================================
    // CREATE — Formulaire de création
    // ============================================================

    public function create(): View
    {
        // ✅ FIX : utilise le service qui filtre correctement les users avec salaire > 0
        $users     = $this->avanceService->getUsersAvecSalaireFixe();
        $usersData = $this->avanceService->getUsersData($users);

        $mois       = MoisScolaire::orderBy('mois')->get();
        $tauxChange = $this->avanceService->getTauxChangeSafe();

        return view('admin.avances.create', compact('users', 'mois', 'tauxChange', 'usersData'));
    }

    // ============================================================
    // STORE — Enregistrer une nouvelle avance
    // ============================================================

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAvance($request);

        $user = User::findOrFail($data['user_id']);

        // ✅ FIX PHP 8.5 : cast (float) avant round()
        $montantAvance = (int) round((float) $data['montant_avance_usd']);

        try {
            $avance = $this->avanceService->creerAvance($user, [
                'montant_avance_usd' => $montantAvance,
                'mois_scolaire_id'   => $data['mois_scolaire_id'],
                'date_avance'        => $data['date_avance'],
                'motif'              => $data['motif'] ?? null,
                'commentaire'        => $data['commentaire'] ?? null,
            ]);

            $this->avertirDepassementSalaire($user, $montantAvance);

            Log::info('Avance créée manuellement (admin)', [
                'avance_id' => $avance->id,
                'user_id'   => $user->id,
                'montant'   => $montantAvance,
                'admin_id'  => auth()->id(),
            ]);

            return redirect()
                ->route('admin.avances.index')
                ->with('success', "Avance de " . number_format($montantAvance, 0, ',', ' ') . " $ enregistrée avec succès pour {$user->name}.");

        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());

        } catch (Throwable $e) {
            Log::error('Erreur création avance', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de la création. Veuillez réessayer.');
        }
    }

    // ============================================================
    // EDIT — Formulaire d'édition
    // ============================================================

    public function edit(AvanceSalaire $avance): View
    {
        $avance->load(['user', 'moisScolaire', 'remboursements']);

        // ✅ Charge les users avec salaire fixé
        $users = $this->avanceService->getUsersAvecSalaireFixe();

        // S'assurer que l'utilisateur actuel de l'avance est dans la liste
        if ($avance->user && ! $users->contains('id', $avance->user_id)) {
            $users->push($avance->user);
        }

        $usersData  = $this->avanceService->getUsersData($users);
        $mois       = MoisScolaire::orderBy('mois')->get();
        $tauxChange = $this->avanceService->getTauxChangeSafe();

        $peutEtreModifiee   = $avance->peutEtreModifiee();
        $aDesRemboursements = $avance->remboursements()->exists();

        return view('admin.avances.edit', compact(
            'avance',
            'users',
            'mois',
            'tauxChange',
            'usersData',
            'peutEtreModifiee',
            'aDesRemboursements'
        ));
    }

    // ============================================================
    // UPDATE — Mettre à jour une avance
    // ============================================================

    public function update(Request $request, AvanceSalaire $avance): RedirectResponse
    {
        $data = $this->validateAvance($request, true);

        $user = User::findOrFail($data['user_id']);

        // ✅ FIX PHP 8.5 : cast (float) avant round()
        $nouveauMontant = (int) round((float) $data['montant_avance_usd']);
        $rembourse      = (int) $avance->montant_rembourse_usd;

        // Blocage si déjà remboursée et montant modifié
        if ($avance->remboursements()->exists() && $nouveauMontant !== (int) $avance->montant_avance_usd) {
            return back()
                ->withInput()
                ->with('error', 'Cette avance a déjà été partiellement remboursée. Vous ne pouvez pas modifier son montant.');
        }

        // Vérification limite si le montant augmente
        if ($nouveauMontant > (int) $avance->montant_avance_usd) {
            $detteHorsAvance     = $this->avanceService->getDetteTotale($user) - (int) $avance->dette_restante_usd;
            $nouvelleDetteTotale = $detteHorsAvance + max(0, $nouveauMontant - $rembourse);
            $limite              = $this->avanceService->getLimiteEmprunt($user);

            if ($nouvelleDetteTotale > $limite) {
                return back()->withInput()->with('error', sprintf(
                    "La modification ferait dépasser la limite d'emprunt. Dette totale après modification : %d $, limite : %d $.",
                    $nouvelleDetteTotale,
                    $limite
                ));
            }
        }

        try {
            DB::transaction(function () use ($avance, $data, $user, $nouveauMontant, $rembourse): void {
                $taux = $this->avanceService->getTauxChangeSafe();

                $detteRestante = max(0, $nouveauMontant - $rembourse);

                $statut = $data['statut'];
                if ($detteRestante === 0 && $statut === AvanceSalaire::STATUT_EN_ATTENTE) {
                    $statut = AvanceSalaire::STATUT_REMBOURSEE;
                }

                $avance->update([
                    'user_id'            => $user->id,
                    'mois_scolaire_id'   => $data['mois_scolaire_id'],
                    'montant_avance_usd' => $nouveauMontant,
                    'montant_avance_fc'  => (int) round($nouveauMontant * $taux),
                    'date_avance'        => $data['date_avance'],
                    'motif'              => $data['motif'] ?? null,
                    'commentaire'        => $data['commentaire'] ?? null,
                    'statut'             => $statut,
                    'dette_restante_usd' => $detteRestante,
                    'dette_restante_fc'  => (int) round($detteRestante * $taux),
                ]);

                $this->avanceService->invaliderCacheDette($user);
                $this->avertirDepassementSalaire($user, $nouveauMontant);
            });

            Log::info('Avance mise à jour (admin)', [
                'avance_id'       => $avance->id,
                'user_id'         => $user->id,
                'nouveau_montant' => $nouveauMontant,
                'admin_id'        => auth()->id(),
            ]);

            return redirect()
                ->route('admin.avances.index')
                ->with('success', "Avance #{$avance->id} mise à jour avec succès.");

        } catch (\DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());

        } catch (Throwable $e) {
            Log::error('Erreur mise à jour avance', [
                'avance_id' => $avance->id,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return back()->withInput()
                ->with('error', 'Une erreur est survenue lors de la mise à jour. Veuillez réessayer.');
        }
    }

    // ============================================================
    // DESTROY — Supprimer une avance
    // ============================================================

    public function destroy(AvanceSalaire $avance): RedirectResponse
    {
        if (! $avance->peutEtreSupprimee()) {
            return back()->with(
                'error',
                "Impossible de supprimer une avance qui a déjà été remboursée partiellement ou totalement. Vous pouvez l'annuler à la place."
            );
        }

        $userId = $avance->user_id;

        try {
            DB::transaction(function () use ($avance): void {
                $avance->delete();
            });

            if ($userId) {
                cache()->forget('dettes_actives_user_' . $userId);
            }

            Log::info('Avance supprimée (admin)', [
                'avance_id' => $avance->id,
                'user_id'   => $userId,
                'admin_id'  => auth()->id(),
            ]);

            return redirect()
                ->route('admin.avances.index')
                ->with('success', "Avance #{$avance->id} supprimée avec succès.");

        } catch (Throwable $e) {
            Log::error('Erreur suppression avance', [
                'avance_id' => $avance->id,
                'error'     => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la suppression. Veuillez réessayer.');
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Valide la requête d'avance (création ou mise à jour).
     */
    private function validateAvance(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'user_id'            => 'required|integer|exists:users,id',
            'mois_scolaire_id'   => 'required|integer|exists:mois_scolaires,id',
            'montant_avance_usd' => 'required|numeric|min:1|max:1000000',
            'date_avance'        => 'required|date',
            'motif'              => 'nullable|string|max:255',
            'commentaire'        => 'nullable|string|max:1000',
        ];

        if ($isUpdate) {
            $rules['statut'] = 'required|in:' . implode(',', AvanceSalaire::STATUTS);
        }

        return $request->validate($rules, [
            'user_id.required'            => 'Le bénéficiaire est obligatoire.',
            'user_id.exists'              => "L'utilisateur sélectionné est invalide.",
            'mois_scolaire_id.required'   => 'Le mois scolaire est obligatoire.',
            'mois_scolaire_id.exists'     => 'Le mois scolaire sélectionné est invalide.',
            'montant_avance_usd.required' => "Le montant de l'avance est obligatoire.",
            'montant_avance_usd.numeric'  => 'Le montant doit être un nombre valide.',
            'montant_avance_usd.min'      => 'Le montant doit être supérieur à 0.',
            'montant_avance_usd.max'      => 'Le montant ne peut pas dépasser 1 000 000 $.',
            'date_avance.required'        => "La date de l'avance est obligatoire.",
            'date_avance.date'            => "La date de l'avance doit être valide.",
            'motif.max'                   => 'Le motif ne peut pas dépasser 255 caractères.',
            'commentaire.max'             => 'Le commentaire ne peut pas dépasser 1000 caractères.',
            'statut.required'             => 'Le statut est obligatoire.',
            'statut.in'                   => 'Le statut sélectionné est invalide.',
        ]);
    }

    /**
     * Affiche un avertissement si l'avance dépasse le salaire mensuel.
     */
    private function avertirDepassementSalaire(User $user, int $montantAvance): void
    {
        $salaireMensuel = $this->avanceService->getSalaireMensuelUsd($user);

        if ($salaireMensuel > 0 && $montantAvance > $salaireMensuel) {
            session()->flash('warning', sprintf(
                "L'avance de %s $ dépasse le salaire mensuel de %s (%d $). La dette sera reportée sur les mois suivants.",
                number_format($montantAvance, 0, ',', ' '),
                $user->name,
                $salaireMensuel
            ));
        }
    }
}