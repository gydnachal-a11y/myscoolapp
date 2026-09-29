<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Annonce;
use App\Models\AvanceSalaire;
use App\Models\DemandeAvance;
use App\Models\Devise;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\PaiementSalaire;
use App\Models\PeriodeNote;
use App\Models\SessionAvance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const ANNONCES_LIMIT        = 3;
    private const DEMANDES_LIMIT        = 10;
    private const PAIEMENTS_LIMIT       = 6;
    private const PROJECTION_MAX_MOIS   = 24;

    private const CACHE_TTL_STATS       = 600;   // 10 min
    private const CACHE_TTL_TAUX        = 3600;  // 1 h
    private const CACHE_TTL_NOTIFS      = 30;    // 30 s

    private const DEVISE_SOURCE = 'USD';
    private const DEVISE_CIBLE  = 'CDF';
    private const TAUX_FALLBACK = 2800.0;

    private const STATUTS_AVANCE_ACTIVE = [
        'en_attente',
        'partiellement_remboursee',
    ];

    // ============================================================
    // INDEX
    // ============================================================

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        /* ---------------------------------------------------------
         | 1. Données statiques (peuvent être cachées globalement)
         | --------------------------------------------------------- */
        $sessionActive   = $this->getActiveSession();
        $periodesNotes   = $this->getPeriodeNotes($sessionActive);
        $sessionAvance   = SessionAvance::getActive();
        $tauxChange      = $this->getTauxChange();

        /* ---------------------------------------------------------
         | 2. Données du user (calculées une seule fois)
         | --------------------------------------------------------- */
        $salaireBase   = $this->getSalaireBase($user);   // ✅ 1 seule fois
        $salaireData   = $this->getSalaireData($user, $salaireBase);
        $detteData     = $this->getDetteData($user, $salaireBase);

        /* ---------------------------------------------------------
         | 3. Annonces et demandes
         | --------------------------------------------------------- */
        $annonces         = $this->getAnnonces($user);
        $demandesNonVues  = $this->countDemandesNonVues($user);
        $demandesRecentes = $this->getDemandesRecentes($user);

        /* ---------------------------------------------------------
         | 4. Avances + projection
         | --------------------------------------------------------- */
        $avancesActives  = $this->getAvancesActives($user);
        $projectionDette = $this->calculerProjectionDette($salaireBase);
        $moisReprise     = $this->getMoisReprise($projectionDette);

        /* ---------------------------------------------------------
         | 5. Données conditionnelles
         | --------------------------------------------------------- */
        $coursData = $this->getEnseignantData($user);
        $stats     = $user->isSuperAdmin() ? $this->getStatsGlobales() : [];

        return view('dashboard.index', [
            'user'                => $user,
            'sessionActive'       => $sessionActive,
            'periodesNotes'       => $periodesNotes,
            'annoncesVisibles'    => $annonces['visibles'],
            'annoncesNonLues'     => $annonces['nonLues'],
            'salaireData'         => $salaireData,
            'detteData'           => $detteData,
            'tauxChange'          => $tauxChange,
            'coursData'           => $coursData,
            'stats'               => $stats,
            'avancesActives'      => $avancesActives,
            'demandesRecentes'    => $demandesRecentes,
            'projectionDette'     => $projectionDette,
            'moisReprise'         => $moisReprise,
            'sessionAvanceActive' => $sessionAvance,
            'demandesNonVues'     => $demandesNonVues,
        ]);
    }

    // ============================================================
    // MÉTHODES PRIVÉES — DONNÉES STATIQUES
    // ============================================================

    private function getActiveSession(): ?AnneeScolaire
    {
        return AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->first();
    }

    private function getPeriodeNotes(?AnneeScolaire $session): Collection
    {
        if (! $session) {
            return collect();
        }

        return PeriodeNote::where('annee_scolaire_id', $session->id)
            ->orderBy('date_debut')
            ->get();
    }

    private function getTauxChange(): float
    {
        return Cache::remember('taux_change_usd_cdf', self::CACHE_TTL_TAUX, function (): float {
            $source = Devise::where('code', self::DEVISE_SOURCE)->first();
            $cible  = Devise::where('code', self::DEVISE_CIBLE)->first();

            return $source && $cible
                ? (float) $source->tauxVers($cible)
                : self::TAUX_FALLBACK;
        });
    }

    // ============================================================
    // MÉTHODES PRIVÉES — DONNÉES DU USER
    // ============================================================

    private function getSalaireBase(User $user): float
    {
        $dernier = PaiementSalaire::where('user_id', $user->id)
            ->orderByDesc('date_paiement')
            ->value('montant_attendu_usd');

        return $dernier !== null
            ? (float) $dernier
            : (float) ($user->salaire_base ?? 0);
    }

    private function getSalaireData(User $user, float $salaireBase): array
    {
        $paiements = PaiementSalaire::where('user_id', $user->id)
            ->with('moisScolaire')
            ->orderByDesc('date_paiement')
            ->take(self::PAIEMENTS_LIMIT)
            ->get();

        $totalPercu   = (float) $paiements->sum('montant_paye_usd');
        $totalAttendu = (float) $paiements->sum('montant_attendu_usd');

        return [
            'salaireBase'     => $salaireBase,
            'paiements'       => $paiements,
            'totalPerçu'      => $totalPercu,
            'totalAttendu'    => $totalAttendu,
            'solde'           => $totalAttendu - $totalPercu,
            'dernierPaiement' => $paiements->first(),
        ];
    }

    private function getDetteData(User $user, float $salaireBase): array
    {
        $detteTotale = (float) AvanceSalaire::where('user_id', $user->id)
            ->whereIn('statut', self::STATUTS_AVANCE_ACTIVE)
            ->sum('dette_restante_usd');

        $deductionMensuelle = min($salaireBase, $detteTotale);

        return [
            'detteTotale'         => $detteTotale,
            'deductionMensuelle'  => $deductionMensuelle,
            'resteApresDeduction' => $detteTotale - $deductionMensuelle,
            'aUneDette'           => $detteTotale > 0,
        ];
    }

    // ============================================================
    // MÉTHODES PRIVÉES — ANNONCES
    // ============================================================

    private function getAnnonces(?User $user): array
    {
        $baseQuery = Annonce::active()->orderByDesc('date_debut');

        /* ---------------------------------------------------------
         | Visiteur anonyme
         | --------------------------------------------------------- */
        if (! $user) {
            return [
                'visibles' => $baseQuery->where('type', 'public')
                    ->take(self::ANNONCES_LIMIT)
                    ->get(),
                'nonLues'  => 0,
            ];
        }

        /* ---------------------------------------------------------
         | User connecté — annonces visibles (public + privé accessible)
         | --------------------------------------------------------- */
        $visibles = (clone $baseQuery)
            ->where(function ($q) use ($user): void {
                $q->where('type', 'public')
                  ->orWhere(function ($q2) use ($user): void {
                      $q2->where('type', 'prive')
                         ->whereHas('lecteurs', fn ($q3) => $q3->where('user_id', $user->id));
                  });
            })
            ->take(self::ANNONCES_LIMIT)
            ->get();

        /* ---------------------------------------------------------
         | Annonces non lues
         | --------------------------------------------------------- */
        $nonLues = (clone $baseQuery)
            ->whereDoesntHave('lecteurs', function ($q) use ($user): void {
                $q->where('user_id', $user->id)
                  ->whereNotNull('lu_a');
            })
            ->count();

        return [
            'visibles' => $visibles,
            'nonLues'  => $nonLues,
        ];
    }

    // ============================================================
    // MÉTHODES PRIVÉES — DEMANDES & AVANCES
    // ============================================================

    /**
     * ✅ Compte les demandes non vues (avec cache 30 s).
     */
    private function countDemandesNonVues(User $user): int
    {
        return Cache::remember(
            "notifs_non_vues_user_{$user->id}",
            self::CACHE_TTL_NOTIFS,
            fn (): int => DemandeAvance::countNotifieesNonVuesPour($user->id)
        );
    }

    private function getDemandesRecentes(User $user): Collection
    {
        return DemandeAvance::where('user_id', $user->id)
            ->with(['session', 'traitePar', 'avance'])
            ->orderByDesc('created_at')
            ->take(self::DEMANDES_LIMIT)
            ->get();
    }

    private function getAvancesActives(User $user): Collection
    {
        return AvanceSalaire::where('user_id', $user->id)
            ->whereIn('statut', self::STATUTS_AVANCE_ACTIVE)
            ->with(['moisScolaire'])
            ->orderBy('date_avance')
            ->get();
    }

    // ============================================================
    // MÉTHODES PRIVÉES — PROJECTION DETTE
    // ============================================================

    private function calculerProjectionDette(float $salaireMensuel): array
    {
        if ($salaireMensuel <= 0) {
            return [];
        }

        $detteRestante = (float) AvanceSalaire::where('user_id', Auth::id())
            ->whereIn('statut', self::STATUTS_AVANCE_ACTIVE)
            ->sum('dette_restante_usd');

        if ($detteRestante <= 0) {
            return [];
        }

        $projection = [];
        $mois       = now()->startOfMonth();

        for ($i = 0; $i < self::PROJECTION_MAX_MOIS && $detteRestante > 0; $i++) {
            $deduction = min($salaireMensuel, $detteRestante);
            $detteFin  = max(0.0, $detteRestante - $deduction);

            $projection[] = [
                'mois'            => $mois->translatedFormat('M Y'),
                'mois_key'        => $mois->format('Y-m'),
                'dette_debut'     => round($detteRestante, 2),
                'deduction'       => round($deduction, 2),
                'dette_fin'       => round($detteFin, 2),
                'net_a_percevoir' => round($salaireMensuel - $deduction, 2),
                'solde'           => $detteFin <= 0,
            ];

            $detteRestante = $detteFin;
            $mois->addMonth();
        }

        return $projection;
    }

    private function getMoisReprise(array $projection): ?array
    {
        if (empty($projection)) {
            return null;
        }

        foreach ($projection as $ligne) {
            if ($ligne['solde']) {
                return [
                    'mois'    => $ligne['mois'],
                    'montant' => $ligne['net_a_percevoir'],
                ];
            }
        }

        return null;
    }

    // ============================================================
    // MÉTHODES PRIVÉES — ENSEIGNANT
    // ============================================================

    private function getEnseignantData(User $user): array
    {
        if (! $user->isEnseignant() && ! $user->hasRole('enseignant')) {
            return ['cours' => collect(), 'heuresTotales' => 0];
        }

        $cours = $user->coursEnseignes()
            ->with(['cour', 'salle', 'nombreHeure'])
            ->get();

        return [
            'cours'         => $cours,
            'heuresTotales' => $cours->sum(
                fn ($c) => (int) ($c->nombreHeure->valeur ?? 0)
            ),
        ];
    }

    // ============================================================
    // MÉTHODES PRIVÉES — STATS GLOBALES
    // ============================================================

    private function getStatsGlobales(): array
    {
        return Cache::remember('dashboard_stats_globales', self::CACHE_TTL_STATS, function (): array {
            return [
                'users'        => User::count(),
                'eleves'       => Eleve::count(),
                'inscriptions' => Inscription::count(),
                'paiements'    => (float) Paiement::sum('montant_paye_usd'),
            ];
        });
    }
}