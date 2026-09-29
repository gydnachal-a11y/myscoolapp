<?php

namespace App\Services;

use App\Models\AvanceSalaire;
use App\Models\Devise;
use App\Models\PaiementSalaire;
use App\Models\RemboursementAvance;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AvanceService
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    /** Multiplicateur de la limite d'emprunt (2 × salaire mensuel). */
    private const LIMITE_MULTIPLE = 2;

    /** Durée du cache du taux de change (1 heure). */
    private const TAUX_CACHE_TTL = 3600;

    /** Durée du cache des dettes utilisateur (5 minutes). */
    private const DETTE_CACHE_TTL = 300;

    /** Taux de change par défaut (fallback). */
    private const TAUX_CHANGE_DEFAUT = 2800.0;

    /** Statuts d'une avance encore active (non soldée). */
    private const STATUTS_ACTIFS = ['en_attente', 'partiellement_remboursee'];

    /** Clés de cache. */
    private const CACHE_KEY_TAUX   = 'taux_change_usd_cdf';
    private const CACHE_KEY_DETTES = 'dettes_actives_user_';

    // ============================================================
    // LECTURE — SALAIRE & LIMITE
    // ============================================================

    /**
     * Récupère le salaire mensuel en USD (arrondi à l'entier).
     */
    public function getSalaireMensuelUsd(User $user): int
    {
        $salaire = match ($user->type_salaire) {
            'manuel' => (float) ($user->salaire_mensuel_usd ?? 0),
            default  => (float) ($user->salaire_ajuste_usd ?? $user->salaire_auto_base_usd ?? 0),
        };

        return (int) round($salaire);
    }

    /**
     * Limite d'emprunt = 2 × salaire mensuel.
     */
    public function getLimiteEmprunt(User $user): int
    {
        return self::LIMITE_MULTIPLE * $this->getSalaireMensuelUsd($user);
    }

    // ============================================================
    // LECTURE — DETTES
    // ============================================================

    /**
     * Dette totale (somme des dettes restantes des avances non soldées).
     * Résultat mis en cache 5 minutes pour éviter les requêtes répétées.
     */
    public function getDetteTotale(User $user): int
    {
        $cacheKey = self::CACHE_KEY_DETTES . $user->id;

        return (int) Cache::remember($cacheKey, self::DETTE_CACHE_TTL, function () use ($user) {
            return (int) AvanceSalaire::where('user_id', $user->id)
                ->whereIn('statut', self::STATUTS_ACTIFS)
                ->sum('dette_restante_usd');
        });
    }

    /**
     * Dette pour un mois scolaire spécifique.
     */
    public function getDetteDuMois(User $user, int $moisScolaireId): int
    {
        return (int) AvanceSalaire::where('user_id', $user->id)
            ->whereIn('statut', self::STATUTS_ACTIFS)
            ->where('mois_scolaire_id', $moisScolaireId)
            ->sum('dette_restante_usd');
    }

    /**
     * Montant encore disponible pour de nouvelles avances.
     */
    public function getResteDisponible(User $user): int
    {
        return max(0, $this->getLimiteEmprunt($user) - $this->getDetteTotale($user));
    }

    /**
     * Vérifie si l'employé a atteint sa limite d'emprunt.
     */
    public function estBloque(User $user): bool
    {
        return $this->getDetteTotale($user) >= $this->getLimiteEmprunt($user);
    }

    /**
     * Vérifie si l'employé peut emprunter un montant supplémentaire.
     */
    public function peutEmprunter(User $user, float $montant): bool
    {
        $montant = (int) round($montant);

        return ($this->getDetteTotale($user) + $montant) <= $this->getLimiteEmprunt($user);
    }

    /**
     * Retourne la situation financière complète d'un utilisateur.
     * Utile pour les vues : un seul appel renvoie toutes les données.
     *
     * @return array{salaire: int, dette: int, limite: int, disponible: int, bloque: bool, taux_change: float}
     */
    public function getSituationFinanciere(User $user): array
    {
        $salaire   = $this->getSalaireMensuelUsd($user);
        $dette     = $this->getDetteTotale($user);
        $limite    = $this->getLimiteEmprunt($user);
        $disponible = max(0, $limite - $dette);

        return [
            'salaire'      => $salaire,
            'dette'        => $dette,
            'limite'       => $limite,
            'disponible'   => $disponible,
            'bloque'       => $dette >= $limite,
            'taux_change'  => $this->getTauxChange(),
        ];
    }

    // ============================================================
    // LECTURE — DONNÉES GROUPÉES (évite les N+1)
    // ============================================================

    /**
     * Récupère les données utilisateur (salaire, dette, etc.) de manière optimisée.
     *
     * @param  Collection<int, User>  $users
     * @return array<int, array<string, mixed>>
     */
    public function getUsersData(Collection $users): array
    {
        if ($users->isEmpty()) {
            return [];
        }

        $dettesGroup = AvanceSalaire::whereIn('user_id', $users->pluck('id'))
            ->whereIn('statut', self::STATUTS_ACTIFS)
            ->selectRaw('user_id, SUM(dette_restante_usd) as total_dette')
            ->groupBy('user_id')
            ->pluck('total_dette', 'user_id');

        return $users->map(function (User $u) use ($dettesGroup) {
            $salaire = $this->getSalaireMensuelUsd($u);
            $dette   = (int) ($dettesGroup[$u->id] ?? 0);
            $limite  = $this->getLimiteEmprunt($u);

            return [
                'id'               => $u->id,
                'name'             => $u->name,
                'salaire'          => $salaire,
                'dette'            => $dette,
                'limite'           => $limite,
                'reste_disponible' => max(0, $limite - $dette),
                'bloque'           => $dette >= $limite,
            ];
        })->values()->toArray();
    }

    // ============================================================
    // ÉCRITURE — CRÉATION D'UNE AVANCE
    // ============================================================

    /**
     * Crée une avance après vérification des règles métier.
     *
     * @param  array{montant_avance_usd: float|int, mois_scolaire_id: int, date_avance: string, motif?: string|null, commentaire?: string|null}  $data
     *
     * @throws \DomainException  Si la limite d'emprunt est dépassée
     */
    public function creerAvance(User $user, array $data): AvanceSalaire
    {
        $montantAvance = (int) round($data['montant_avance_usd']);

        if ($montantAvance <= 0) {
            throw new \DomainException('Le montant de l\'avance doit être supérieur à zéro.');
        }

        if (!$this->peutEmprunter($user, $montantAvance)) {
            throw new \DomainException(sprintf(
                "Limite d'emprunt dépassée. Dette actuelle : %d $, demande : %d $, limite : %d $.",
                $this->getDetteTotale($user),
                $montantAvance,
                $this->getLimiteEmprunt($user)
            ));
        }

        $taux = $this->getTauxChange();

        $avance = DB::transaction(function () use ($user, $data, $montantAvance, $taux) {
            return AvanceSalaire::create([
                'user_id'               => $user->id,
                'mois_scolaire_id'      => $data['mois_scolaire_id'],
                'montant_avance_usd'    => $montantAvance,
                'montant_avance_fc'     => (int) round($montantAvance * $taux),
                'date_avance'           => $data['date_avance'],
                'motif'                 => $data['motif'] ?? null,
                'commentaire'           => $data['commentaire'] ?? null,
                'statut'                => 'en_attente',
                'montant_rembourse_usd' => 0,
                'montant_rembourse_fc'  => 0,
                'dette_restante_usd'    => $montantAvance,
                'dette_restante_fc'     => (int) round($montantAvance * $taux),
            ]);
        });

        // Invalider le cache de dette de cet utilisateur
        $this->invaliderCacheDette($user);

        // Log hors transaction
        Log::info('Avance créée', [
            'avance_id'   => $avance->id,
            'user_id'     => $user->id,
            'montant_usd' => $montantAvance,
            'taux_change' => $taux,
        ]);

        return $avance;
    }

    // ============================================================
    // REMBOURSEMENT — FIFO
    // ============================================================

    /**
     * Rembourse les avances en attente selon le principe FIFO.
     *
     * @param  User    $user
     * @param  float   $montant              Montant à rembourser (en USD)
     * @param  int|null $paiementSalaireId
     * @param  float   $tauxChange           Taux de change du jour du paiement
     * @return array<int, array<string, mixed>>  Détail des remboursements effectués
     */
    public function rembourserDettes(
        User $user,
        float $montant,
        ?int $paiementSalaireId,
        float $tauxChange
    ): array {
        if ($montant <= 0) {
            return [];
        }

        $avances = AvanceSalaire::where('user_id', $user->id)
            ->whereIn('statut', self::STATUTS_ACTIFS)
            ->orderBy('date_avance')
            ->get();

        if ($avances->isEmpty()) {
            return [];
        }

        $remboursements = [];
        $montantRestant = (int) round($montant);

        DB::transaction(function () use (
            $avances,
            &$montantRestant,
            $tauxChange,
            $paiementSalaireId,
            &$remboursements
        ) {
            foreach ($avances as $avance) {
                if ($montantRestant <= 0) {
                    break;
                }

                $detteRestante = (int) $avance->dette_restante_usd;
                if ($detteRestante <= 0) {
                    continue;
                }

                $rembourseIci     = min($montantRestant, $detteRestante);
                $montantRestant  -= $rembourseIci;

                $nouveauRembourse = (int) $avance->montant_rembourse_usd + $rembourseIci;
                $nouvelleDette    = max(0, $detteRestante - $rembourseIci);
                $statut           = $nouvelleDette === 0 ? 'remboursee' : 'partiellement_remboursee';

                $avance->update([
                    'montant_rembourse_usd' => $nouveauRembourse,
                    'montant_rembourse_fc'  => (int) round($nouveauRembourse * $tauxChange),
                    'dette_restante_usd'    => $nouvelleDette,
                    'dette_restante_fc'     => (int) round($nouvelleDette * $tauxChange),
                    'statut'                => $statut,
                ]);

                RemboursementAvance::create([
                    'avance_id'             => $avance->id,
                    'paiement_salaire_id'   => $paiementSalaireId,
                    'montant_rembourse_usd' => $rembourseIci,
                    'montant_rembourse_fc'  => (int) round($rembourseIci * $tauxChange),
                    'taux_change'           => $tauxChange,
                    'date_remboursement'    => now()->toDateString(),
                    'commentaire'           => 'Remboursement automatique lors du paiement de salaire',
                ]);

                $remboursements[] = [
                    'avance_id'             => $avance->id,
                    'montant_rembourse_usd' => $rembourseIci,
                    'montant_rembourse_fc'  => (int) round($rembourseIci * $tauxChange),
                    'statut_avance'         => $statut,
                ];
            }
        });

        // Invalider le cache de dette
        $this->invaliderCacheDette($user);

        // Log hors transaction
        if (!empty($remboursements)) {
            Log::info('Remboursements effectués', [
                'user_id'         => $user->id,
                'montant_total'   => $montant,
                'nb_remboursements' => count($remboursements),
                'paiement_id'     => $paiementSalaireId,
            ]);
        }

        return $remboursements;
    }

    /**
     * Annule les remboursements liés à un paiement de salaire.
     */
    public function annulerRemboursements(PaiementSalaire $paiement): void
    {
        $remboursements = $paiement->remboursementsAvances;

        if ($remboursements->isEmpty()) {
            return;
        }

        $userIds = [];

        DB::transaction(function () use ($remboursements, $paiement, &$userIds) {
            foreach ($remboursements as $remboursement) {
                $avance = $remboursement->avance;
                if (!$avance) {
                    continue;
                }

                $userIds[] = $avance->user_id;

                $nouvelleDette    = (int) $avance->dette_restante_usd + (int) $remboursement->montant_rembourse_usd;
                $nouveauRembourse = max(0, (int) $avance->montant_rembourse_usd - (int) $remboursement->montant_rembourse_usd);

                $taux = (float) ($remboursement->taux_change ?: $this->getTauxChange());

                $statut = $nouvelleDette > 0 ? 'en_attente' : 'remboursee';

                $avance->update([
                    'montant_rembourse_usd' => $nouveauRembourse,
                    'montant_rembourse_fc'  => (int) round($nouveauRembourse * $taux),
                    'dette_restante_usd'    => $nouvelleDette,
                    'dette_restante_fc'     => (int) round($nouvelleDette * $taux),
                    'statut'                => $statut,
                ]);
            }

            $paiement->remboursementsAvances()->delete();
        });

        // Invalider le cache pour chaque utilisateur affecté
        foreach (array_unique($userIds) as $userId) {
            Cache::forget(self::CACHE_KEY_DETTES . $userId);
        }

        Log::info('Remboursements annulés', [
            'paiement_id'      => $paiement->id,
            'nb_remboursements' => $remboursements->count(),
        ]);
    }

    // ============================================================
    // TAUX DE CHANGE
    // ============================================================

    /**
     * Récupère le taux de change USD → CDF (avec cache 1h).
     *
     * @throws \RuntimeException  Si le taux n'est pas configuré
     */
    public function getTauxChange(): float
    {
        return Cache::remember(self::CACHE_KEY_TAUX, self::TAUX_CACHE_TTL, function () {
            $source = Devise::where('code', 'USD')->first();
            $cible  = Devise::where('code', 'CDF')->first();

            if (!$source || !$cible) {
                throw new \RuntimeException(
                    'Taux de change USD/CDF non configuré dans la base de données.'
                );
            }

            return (float) $source->tauxVers($cible);
        });
    }

    /**
     * Version "safe" de getTauxChange() : retourne un taux par défaut
     * au lieu de lancer une exception si non configuré.
     */
    public function getTauxChangeSafe(): float
    {
        try {
            return $this->getTauxChange();
        } catch (\Throwable $e) {
            Log::warning('Taux de change non configuré, utilisation du fallback', [
                'fallback' => self::TAUX_CHANGE_DEFAUT,
            ]);

            return self::TAUX_CHANGE_DEFAUT;
        }
    }

    /**
     * Force le rechargement du taux de change (utile après modification par l'admin).
     */
    public function refreshTauxChange(): float
    {
        Cache::forget(self::CACHE_KEY_TAUX);

        return $this->getTauxChange();
    }

    // ============================================================
    // CACHE — UTILITAIRES
    // ============================================================

    /**
     * Invalide le cache de dette d'un utilisateur.
     */
    public function invaliderCacheDette(User $user): void
    {
        Cache::forget(self::CACHE_KEY_DETTES . $user->id);
    }

    /**
     * Invalide le cache de dettes de plusieurs utilisateurs.
     *
     * @param  iterable<int>  $userIds
     */
    public function invaliderCacheDettes(iterable $userIds): void
    {
        foreach ($userIds as $userId) {
            Cache::forget(self::CACHE_KEY_DETTES . $userId);
        }
    }
}