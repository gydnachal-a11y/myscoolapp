<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourSalle;
use App\Models\Devise;
use App\Models\SalaireHoraire;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AvanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class SalaireController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const CACHE_TTL               = 3600;
    private const PER_PAGE                = 20;
    private const RECENT_PAYMENTS_LIMIT   = 12;
    private const STATUTS_AVANCE_EN_COURS = ['en_attente', 'partiellement_remboursee'];

    private const CACHE_KEY_TAUX_HORAIRE = 'taux_horaire_actif';
    private const CACHE_KEY_TAUX_CHANGE  = 'taux_change';

    // ============================================================
    // CONSTRUCTEUR
    // ============================================================

    public function __construct(
        protected AvanceService $avanceService,
    ) {}

    // ============================================================
    // MÉTHODES PRIVÉES — CACHE & TAUX
    // ============================================================

    private function getTauxHoraireActif(): float
    {
        $taux = Cache::get(self::CACHE_KEY_TAUX_HORAIRE);

        if (! is_numeric($taux)) {
            $taux = SalaireHoraire::where('actif', true)
                ->latest()
                ->value('taux_usd');

            Cache::put(self::CACHE_KEY_TAUX_HORAIRE, $taux, self::CACHE_TTL);
        }

        if (! $taux) {
            throw new RuntimeException(
                "Aucun taux horaire actif n'est configuré. Veuillez en définir un."
            );
        }

        return (float) $taux;
    }

    private function getTauxChange(): float
    {
        $taux = $this->getTauxChangeSafe();

        if ($taux === null) {
            throw new RuntimeException(
                "Aucun taux de change n'est configuré. "
                . "Veuillez le définir dans Admin → Taux de change."
            );
        }

        return $taux;
    }

    private function getTauxChangeSafe(): ?float
    {
        $cached = Cache::get(self::CACHE_KEY_TAUX_CHANGE);

        if ($cached !== null && is_numeric($cached) && (float) $cached > 0) {
            return (float) $cached;
        }

        $taux = $this->resolveTauxChangeFromSources();

        if ($taux !== null && $taux > 0) {
            Cache::put(self::CACHE_KEY_TAUX_CHANGE, $taux, self::CACHE_TTL);
            return $taux;
        }

        return null;
    }

    private function resolveTauxChangeFromSources(): ?float
    {
        try {
            $taux = $this->avanceService->getTauxChangeSafe();
            if ($taux !== null && (float) $taux > 0) {
                return (float) $taux;
            }
        } catch (Throwable $e) {
            Log::debug('AvanceService::getTauxChangeSafe a échoué', ['error' => $e->getMessage()]);
        }

        try {
            $taux = Cache::get('taux_change');
            if (is_numeric($taux) && (float) $taux > 0) {
                return (float) $taux;
            }
        } catch (Throwable) {}

        try {
            $source = Devise::where('code', 'USD')->first();
            $cible  = Devise::where('code', 'CDF')->first();

            if ($source && $cible && method_exists($source, 'tauxVers')) {
                $taux = (float) $source->tauxVers($cible);
                if ($taux > 0) {
                    return $taux;
                }
            }
        } catch (Throwable $e) {
            Log::debug('Devise::tauxVers a échoué', ['error' => $e->getMessage()]);
        }

        try {
            $settings = SiteSetting::getSettings();
            $taux     = $settings->taux_change ?? null;

            if (is_numeric($taux) && (float) $taux > 0) {
                return (float) $taux;
            }
        } catch (Throwable) {}

        return null;
    }

    private function clearSalaryCache(): void
    {
        Cache::forget(self::CACHE_KEY_TAUX_HORAIRE);
        Cache::forget(self::CACHE_KEY_TAUX_CHANGE);
        Cache::forget('taux_change');
    }

    // ============================================================
    // MÉTHODES PRIVÉES — CALCULS
    // ============================================================

    private function calculerHeuresHebdo(User $user): float
    {
        $total = 0;

        $assignations = CourSalle::with('creneauHoraire')
            ->where('titulaire_id', $user->id)
            ->get();

        foreach ($assignations as $assign) {
            if (! $assign->creneauHoraire) {
                continue;
            }

            $dureeSeance = $assign->creneauHoraire->heure_debut
                ->diffInMinutes($assign->creneauHoraire->heure_fin) / 60;

            $nbSeances = (int) ($assign->nombre_seances ?? 0);
            $total += $dureeSeance * $nbSeances;
        }

        return round($total, 2);
    }

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

    private function preparerCalculsSalaire(User $user): array
    {
        $tauxChange = $this->getTauxChangeSafe();

        try {
            if ($tauxChange === null) {
                throw new RuntimeException('Taux de change manquant.');
            }

            $tauxHoraire        = $this->getTauxHoraireActif();
            $heuresHebdo        = $this->calculerHeuresHebdo($user);
            $salaireAutoBaseUsd = round($heuresHebdo * $tauxHoraire, 2);
            $salaireAutoBaseFc  = round($salaireAutoBaseUsd * $tauxChange, 2);
        } catch (RuntimeException) {
            $tauxHoraire        = null;
            $heuresHebdo        = 0.0;
            $salaireAutoBaseUsd = 0.0;
            $salaireAutoBaseFc  = 0.0;
        }

        return [
            'tauxHoraire'        => $tauxHoraire,
            'tauxChange'         => $tauxChange,
            'heuresHebdo'        => $heuresHebdo,
            'salaireAutoBaseUsd' => $salaireAutoBaseUsd,
            'salaireAutoBaseFc'  => $salaireAutoBaseFc,
        ];
    }

    // ============================================================
    // INDEX
    // ============================================================

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
            $query->where('type_salaire', $request->input('type_salaire'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search): void {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->appends($request->except('page'));

        $nbAutomatique = User::where('type_salaire', 'automatique')->count();
        $nbManuel      = User::where('type_salaire', 'manuel')->count();

        $totalSalairesUSD = (float) User::sum(
            DB::raw("
                CASE
                    WHEN type_salaire = 'manuel'      THEN COALESCE(salaire_mensuel_usd, 0)
                    WHEN type_salaire = 'automatique' THEN COALESCE(salaire_ajuste_usd, salaire_auto_base_usd, 0)
                    ELSE 0
                END
            ")
        );

        $totalSalairesFC = (float) User::sum(
            DB::raw("
                CASE
                    WHEN type_salaire = 'manuel'      THEN COALESCE(salaire_mensuel_fc, 0)
                    WHEN type_salaire = 'automatique' THEN COALESCE(salaire_ajuste_fc, salaire_auto_base_fc, 0)
                    ELSE 0
                END
            ")
        );

        $tauxChange = $this->getTauxChangeSafe();

        if ($tauxChange === null) {
            session()->flash(
                'warning',
                "Aucun taux de change n'est configuré. "
                . "Veuillez le définir dans Admin → Taux de change."
            );
        }

        return view('admin.salaires.index', [
            'users'            => $users,
            'nbAutomatique'    => $nbAutomatique,
            'nbManuel'         => $nbManuel,
            'totalSalairesUSD' => $totalSalairesUSD,
            'totalSalairesFC'  => $totalSalairesFC,
            'tauxChange'       => $tauxChange,
        ]);
    }

    // ============================================================
    // SHOW
    // ============================================================

    public function show(?User $user = null): View|RedirectResponse
    {
        $user = $user ?? auth()->user();

        if (! $user instanceof User) {
            return redirect()->route('login')
                ->with('error', 'Utilisateur introuvable.');
        }

        $calculs = $this->preparerCalculsSalaire($user);

        $paiements = $user->paiementsSalaires()
            ->latest()
            ->take(self::RECENT_PAYMENTS_LIMIT)
            ->get();

        $avances = $user->avances()
            ->whereIn('statut', self::STATUTS_AVANCE_EN_COURS)
            ->orderByDesc('date_avance')
            ->get();

        return view('admin.salaires.show', array_merge($calculs, [
            'user'      => $user,
            'paiements' => $paiements,
            'avances'   => $avances,
        ]));
    }

    // ============================================================
    // EDIT
    // ============================================================

    public function edit(User $user): View
    {
        $calculs = $this->preparerCalculsSalaire($user);

        $assignations = CourSalle::with(['salle', 'creneauHoraire'])
            ->where('titulaire_id', $user->id)
            ->get();

        return view('admin.salaires.edit', array_merge($calculs, [
            'user'         => $user,
            'assignations' => $assignations,
        ]));
    }

    // ============================================================
    // UPDATE — Met à jour le salaire UNIQUEMENT (pas d'avance auto)
    // ============================================================

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'type_salaire'        => 'required|in:manuel,automatique',
            'salaire_mensuel_usd' => 'nullable|numeric|min:0',
            'salaire_ajuste_usd'  => 'nullable|numeric|min:0',
        ]);

        try {
            $tauxChange = $this->getTauxChange();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $ancienSalaire = $user->salaire_mensuel_usd
            ?? $user->salaire_ajuste_usd
            ?? $user->salaire_auto_base_usd
            ?? 0;

        try {
            DB::transaction(function () use ($user, $data, $tauxChange): void {
                if ($data['type_salaire'] === 'manuel') {
                    $salaireUsd = (float) ($data['salaire_mensuel_usd'] ?? 0);

                    // ✅ Update SANS taux_change (colonne inexistante en DB)
                    $user->update([
                        'type_salaire'          => 'manuel',
                        'salaire_mensuel_usd'   => $salaireUsd,
                        'salaire_mensuel_fc'    => round($salaireUsd * $tauxChange, 2),
                        'salaire_auto_base_usd' => null,
                        'salaire_auto_base_fc'  => null,
                        'salaire_ajuste_usd'    => null,
                        'salaire_ajuste_fc'     => null,
                        'date_fixation_salaire' => now(),
                    ]);

                    return;
                }

                // Type automatique
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

                // ✅ Update SANS taux_change
                $user->update([
                    'type_salaire'          => 'automatique',
                    'salaire_mensuel_usd'   => null,
                    'salaire_mensuel_fc'    => null,
                    'salaire_auto_base_usd' => $salaireBaseUsd,
                    'salaire_auto_base_fc'  => $salaireBaseFc,
                    'salaire_ajuste_usd'    => $salaireAjusteUsd,
                    'salaire_ajuste_fc'     => round($salaireAjusteUsd * $tauxChange, 2),
                    'date_fixation_salaire' => now(),
                ]);
            });

            $this->clearSalaryCache();

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();

        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();

        } catch (Throwable $e) {
            Log::error('Erreur lors de la mise à jour du salaire', [
                'user_id'   => $user->id,
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return back()
                ->with('error', 'Une erreur est survenue. Veuillez réessayer.')
                ->withInput();
        }

        $nouveauSalaire = $data['type_salaire'] === 'manuel'
            ? ($data['salaire_mensuel_usd'] ?? 0)
            : ($data['salaire_ajuste_usd'] ?? 0);

        Log::info('Salaire mis à jour', [
            'user_id'             => $user->id,
            'user_name'           => $user->name,
            'type'                => $data['type_salaire'],
            'nouveau_salaire_usd' => $nouveauSalaire,
            'ancien_salaire_usd'  => $ancienSalaire,
            'taux_change'         => $tauxChange,
            'administrateur'      => auth()->id(),
        ]);

        return redirect()
            ->route('admin.salaires.index')
            ->with('success', 'Salaire mis à jour pour ' . $user->name);
    }
}