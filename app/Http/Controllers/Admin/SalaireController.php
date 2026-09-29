<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourSalle;
use App\Models\Devise;
use App\Models\SalaireHoraire;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class SalaireController extends Controller
{
    /** Durée du cache en secondes (1 heure). */
    private const CACHE_TTL = 3600;

    /** Taux de change par défaut si non configuré (FC/USD). */
    private const TAUX_CHANGE_DEFAUT = 2800;

    /** Clés de cache (centralisées pour éviter les fautes de frappe). */
    private const CACHE_KEY_TAUX_HORAIRE = 'taux_horaire_actif';
    private const CACHE_KEY_TAUX_CHANGE  = 'taux_usd_cdf';

    // ============================================================
    // MÉTHODES PRIVÉES (logique métier)
    // ============================================================

    /**
     * Récupère le taux horaire actif (USD/h).
     *
     * @throws RuntimeException si aucun taux n'est configuré
     */
    private function getTauxHoraireActif(): float
    {
        $taux = Cache::get(self::CACHE_KEY_TAUX_HORAIRE);

        if (!is_numeric($taux)) {
            $taux = SalaireHoraire::where('actif', true)->latest()->value('taux_usd');
            Cache::put(self::CACHE_KEY_TAUX_HORAIRE, $taux, self::CACHE_TTL);
        }

        if (!$taux) {
            throw new RuntimeException('Aucun taux horaire actif n\'est configuré. Veuillez en définir un.');
        }

        return (float) $taux;
    }

    /**
     * Récupère le taux de change USD/CDF (avec cache 1h).
     */
    private function getTauxChange(): float
    {
        return Cache::remember(self::CACHE_KEY_TAUX_CHANGE, self::CACHE_TTL, function () {
            $source = Devise::where('code', 'USD')->first();
            $cible  = Devise::where('code', 'CDF')->first();

            if ($source && $cible) {
                return (float) $source->tauxVers($cible);
            }

            return self::TAUX_CHANGE_DEFAUT;
        });
    }

    /**
     * Vide les caches liés aux salaires (à appeler après mise à jour).
     */
    private function clearSalaryCache(): void
    {
        Cache::forget(self::CACHE_KEY_TAUX_HORAIRE);
        Cache::forget(self::CACHE_KEY_TAUX_CHANGE);
    }

    /**
     * Calcule le nombre total d'heures hebdomadaires pour un utilisateur.
     */
    private function calculerHeuresHebdo(User $user): float
    {
        $total = 0;

        $assignations = CourSalle::with('creneauHoraire')
            ->where('titulaire_id', $user->id)
            ->get();

        foreach ($assignations as $assign) {
            if ($assign->creneauHoraire) {
                $dureeSeance = $assign->creneauHoraire->heure_debut
                    ->diffInMinutes($assign->creneauHoraire->heure_fin) / 60;
                $nbSeances = $assign->nombre_seances ?? 0;
                $total += $dureeSeance * $nbSeances;
            }
        }

        return round($total, 2);
    }

    /**
     * Calcule le salaire automatique complet (heures, base USD, base FC).
     */
    private function calculerSalaireAuto(User $user): array
    {
        $heuresHebdo = $this->calculerHeuresHebdo($user);
        $tauxHoraire = $this->getTauxHoraireActif();
        $tauxChange  = $this->getTauxChange();

        $baseUsd = round($heuresHebdo * $tauxHoraire, 2);
        $baseFc  = round($baseUsd * $tauxChange, 2);

        return [
            'heures_hebdo' => $heuresHebdo,
            'base_usd'     => $baseUsd,
            'base_fc'      => $baseFc,
            'taux_horaire' => $tauxHoraire,
            'taux_change'  => $tauxChange,
        ];
    }

    // ============================================================
    // MÉTHODES PUBLIQUES
    // ============================================================

    /**
     * Liste des utilisateurs avec leurs salaires (admin).
     */
    public function index(Request $request): View
    {
        $request->validate([
            'avec_cours'   => 'nullable|boolean',
            'type_salaire' => 'nullable|in:manuel,automatique',
            'search'       => 'nullable|string|max:255',
        ]);

        $query = User::with(['fonction', 'section']);

        if ($request->filled('avec_cours') && $request->boolean('avec_cours')) {
            $query->whereHas('coursEnseignes');
        }

        if ($request->filled('type_salaire')) {
            $query->where('type_salaire', $request->type_salaire);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')
            ->paginate(20)
            ->appends($request->except('page'));

        $nbAutomatique = User::where('type_salaire', 'automatique')->count();
        $nbManuel      = User::where('type_salaire', 'manuel')->count();

        $totalSalairesUSD = (float) User::sum(
            DB::raw("CASE
                WHEN type_salaire = 'manuel'      THEN COALESCE(salaire_mensuel_usd, 0)
                WHEN type_salaire = 'automatique' THEN COALESCE(salaire_ajuste_usd, salaire_auto_base_usd, 0)
                ELSE 0
            END")
        );

        $totalSalairesFC = (float) User::sum(
            DB::raw("CASE
                WHEN type_salaire = 'manuel'      THEN COALESCE(salaire_mensuel_fc, 0)
                WHEN type_salaire = 'automatique' THEN COALESCE(salaire_ajuste_fc, salaire_auto_base_fc, 0)
                ELSE 0
            END")
        );

        $tauxChange = $this->getTauxChange();

        return view('admin.salaires.index', compact(
            'users',
            'nbAutomatique',
            'nbManuel',
            'totalSalairesUSD',
            'totalSalairesFC',
            'tauxChange'
        ));
    }

    /**
     * Affiche la fiche de salaire d'un utilisateur.
     * - En contexte admin : `$user` est fourni via le route model binding.
     * - En contexte membre : `$user` est null → on utilise l'utilisateur connecté.
     */
    public function show(?User $user = null): View|RedirectResponse
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Utilisateur introuvable.');
        }

        $tauxChange = $this->getTauxChange();

        try {
            $tauxHoraire        = $this->getTauxHoraireActif();
            $heuresHebdo        = $this->calculerHeuresHebdo($user);
            $salaireAutoBaseUsd = round($heuresHebdo * $tauxHoraire, 2);
            $salaireAutoBaseFc  = round($salaireAutoBaseUsd * $tauxChange, 2);
        } catch (RuntimeException $e) {
            $tauxHoraire        = null;
            $heuresHebdo        = 0;
            $salaireAutoBaseUsd = 0;
            $salaireAutoBaseFc  = 0;
        }

        // Historique des paiements de salaire (12 derniers)
        $paiements = method_exists($user, 'paiementsSalaires')
            ? $user->paiementsSalaires()->latest()->take(12)->get()
            : collect();

        // Avances en cours
        $avances = method_exists($user, 'avances')
            ? $user->avances()
                ->whereIn('statut', ['en_attente', 'partiellement_remboursee'])
                ->orderByDesc('date_avance')
                ->get()
            : collect();

        return view('admin.salaires.show', compact(
            'user',
            'tauxHoraire',
            'tauxChange',
            'heuresHebdo',
            'salaireAutoBaseUsd',
            'salaireAutoBaseFc',
            'paiements',
            'avances'
        ));
    }

    /**
     * Affiche le formulaire d'édition du salaire.
     */
    public function edit(User $user): View
    {
        $tauxChange = $this->getTauxChange();

        try {
            $tauxHoraire        = $this->getTauxHoraireActif();
            $heuresHebdo        = $this->calculerHeuresHebdo($user);
            $salaireAutoBaseUsd = round($heuresHebdo * $tauxHoraire, 2);
            $salaireAutoBaseFc  = round($salaireAutoBaseUsd * $tauxChange, 2);
        } catch (RuntimeException $e) {
            $tauxHoraire        = null;
            $heuresHebdo        = 0;
            $salaireAutoBaseUsd = 0;
            $salaireAutoBaseFc  = 0;
        }

        $assignations = CourSalle::with(['salle', 'creneauHoraire'])
            ->where('titulaire_id', $user->id)
            ->get();

        return view('admin.salaires.edit', compact(
            'user',
            'tauxHoraire',
            'tauxChange',
            'heuresHebdo',
            'assignations',
            'salaireAutoBaseUsd',
            'salaireAutoBaseFc'
        ));
    }

    /**
     * Met à jour le salaire d'un utilisateur.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'type_salaire'        => 'required|in:manuel,automatique',
            'salaire_mensuel_usd' => 'nullable|numeric|min:0',
            'salaire_ajuste_usd'  => 'nullable|numeric|min:0',
        ]);

        $tauxChange = $this->getTauxChange();

        $ancienSalaire = $user->salaire_mensuel_usd
            ?? $user->salaire_ajuste_usd
            ?? $user->salaire_auto_base_usd
            ?? 0;

        try {
            DB::transaction(function () use ($user, $data, $tauxChange) {
                if ($data['type_salaire'] === 'manuel') {
                    $salaireUsd = (float) ($data['salaire_mensuel_usd'] ?? 0);

                    $user->update([
                        'type_salaire'           => 'manuel',
                        'salaire_mensuel_usd'    => $salaireUsd,
                        'salaire_mensuel_fc'     => round($salaireUsd * $tauxChange, 2),
                        'salaire_auto_base_usd'  => null,
                        'salaire_auto_base_fc'   => null,
                        'salaire_ajuste_usd'     => null,
                        'salaire_ajuste_fc'      => null,
                        'taux_change'            => $tauxChange,
                        'date_fixation_salaire'  => now(),
                    ]);
                } else {
                    $base = $this->calculerSalaireAuto($user);
                    $salaireBaseUsd = $base['base_usd'];
                    $salaireBaseFc  = $base['base_fc'];

                    $salaireAjusteUsd = isset($data['salaire_ajuste_usd'])
                        ? (float) $data['salaire_ajuste_usd']
                        : $salaireBaseUsd;

                    if ($salaireAjusteUsd < 0) {
                        throw ValidationException::withMessages([
                            'salaire_ajuste_usd' => 'Le salaire ajusté ne peut pas être négatif.',
                        ]);
                    }

                    $user->update([
                        'type_salaire'           => 'automatique',
                        'salaire_mensuel_usd'    => null,
                        'salaire_mensuel_fc'     => null,
                        'salaire_auto_base_usd'  => $salaireBaseUsd,
                        'salaire_auto_base_fc'   => $salaireBaseFc,
                        'salaire_ajuste_usd'     => $salaireAjusteUsd,
                        'salaire_ajuste_fc'      => round($salaireAjusteUsd * $tauxChange, 2),
                        'taux_change'            => $tauxChange,
                        'date_fixation_salaire'  => now(),
                    ]);
                }
            });

            // Invalider les caches liés au salaire
            $this->clearSalaryCache();

        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            Log::error('Erreur lors de la mise à jour du salaire', [
                'user_id'   => $user->id,
                'exception' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Une erreur est survenue. Veuillez réessayer.')
                ->withInput();
        }

        $nouveauSalaire = $data['type_salaire'] === 'manuel'
            ? ($data['salaire_mensuel_usd'] ?? 0)
            : ($data['salaire_ajuste_usd'] ?? 0);

        Log::info('Salaire mis à jour', [
            'user_id'            => $user->id,
            'user_name'          => $user->name,
            'type'               => $data['type_salaire'],
            'nouveau_salaire_usd'=> $nouveauSalaire,
            'ancien_salaire_usd' => $ancienSalaire,
            'taux_change'        => $tauxChange,
            'administrateur'     => auth()->id(),
        ]);

        return redirect()
            ->route('admin.salaires.index')
            ->with('success', 'Salaire mis à jour pour ' . $user->name);
    }
}