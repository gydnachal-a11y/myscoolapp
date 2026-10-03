<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    use HasFactory;

    // ============================================================
    // TABLE
    // ============================================================

    protected $table = 'paiements';

    // ============================================================
    // CONSTANTES — Statuts
    // ============================================================

    public const STATUT_IMPAYE  = 'impaye';
    public const STATUT_PARTIEL = 'partiel';
    public const STATUT_PAYE    = 'paye';
    public const STATUT_SURPAYE = 'surpaye';

    public const STATUTS = [
        self::STATUT_IMPAYE,
        self::STATUT_PARTIEL,
        self::STATUT_PAYE,
        self::STATUT_SURPAYE,
    ];

    /** Statuts considérés comme « non soldés ». */
    public const STATUTS_EN_ATTENTE = [
        self::STATUT_IMPAYE,
        self::STATUT_PARTIEL,
    ];

    /** Statuts considérés comme « soldés ». */
    public const STATUTS_SOLDES = [
        self::STATUT_PAYE,
        self::STATUT_SURPAYE,
    ];

    // ============================================================
    // CONSTANTES — Modes de période
    // ============================================================

    public const MODE_MENSUEL = 'mensuel';
    public const MODE_TRANCHE = 'tranche';

    public const MODES = [
        self::MODE_MENSUEL,
        self::MODE_TRANCHE,
    ];

    // ============================================================
    // CONFIGURATION
    // ============================================================

    protected $fillable = [
        'eleve_id',
        'annee_scolaire_id',
        'salle_classe_id',
        'session_paiement_id',
        'type_periode',
        'periode',
        'montant_attendu_usd',
        'montant_attendu_fc',
        'montant_paye_usd',
        'montant_paye_fc',
        'montant_restant_usd',
        'montant_restant_fc',
        'statut',
        'date_paiement',
        'commentaire',
    ];

    /**
     * ✅ Les casts assurent la conversion automatique :
     *    - `eleve_id` : string "546" → int 546 à la lecture
     *    - Plus besoin d'accessor défensif (voir FIX en bas de fichier)
     */
    protected $casts = [
        'id'                  => 'integer',
        'eleve_id'            => 'integer',
        'annee_scolaire_id'   => 'integer',
        'salle_classe_id'     => 'integer',
        'session_paiement_id' => 'integer',
        'date_paiement'       => 'date',
        'montant_attendu_usd' => 'float',
        'montant_attendu_fc'  => 'float',
        'montant_paye_usd'    => 'float',
        'montant_paye_fc'     => 'float',
        'montant_restant_usd' => 'float',
        'montant_restant_fc'  => 'float',
    ];

    protected $appends = [
        'montant_attendu_usd_formate',
        'montant_paye_usd_formate',
        'montant_restant_usd_formate',
        'montant_attendu_fc_formate',
        'montant_paye_fc_formate',
        'montant_restant_fc_formate',
        'pourcentage_paye',
        'statut_libelle',
    ];

    // ============================================================
    // BOOT — Calculs automatiques
    // ============================================================

    protected static function booted(): void
    {
        static::saving(function (self $paiement): void {
            $paiement->recalculerMontants();

            // Date par défaut si un paiement est enregistré
            if (empty($paiement->date_paiement) && (float) $paiement->montant_paye_usd > 0) {
                $paiement->date_paiement = now()->toDateString();
            }
        });
    }

    // ============================================================
    // RELATIONS — Clés étrangères EXPLICITES
    // ============================================================

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class, 'eleve_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function salleClasse(): BelongsTo
    {
        return $this->belongsTo(SalleDeClasse::class, 'salle_classe_id');
    }

    public function sessionPaiement(): BelongsTo
    {
        return $this->belongsTo(SessionPaiement::class, 'session_paiement_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeAnnee(Builder $query, int $anneeId): Builder
    {
        return $query->where('annee_scolaire_id', $anneeId);
    }

    public function scopeSalle(Builder $query, int $salleId): Builder
    {
        return $query->where('salle_classe_id', $salleId);
    }

    public function scopeEleve(Builder $query, int $eleveId): Builder
    {
        return $query->where('eleve_id', $eleveId);
    }

    public function scopeSession(Builder $query, int $sessionId): Builder
    {
        return $query->where('session_paiement_id', $sessionId);
    }

    public function scopeTypePeriode(Builder $query, string $type): Builder
    {
        return $query->where('type_periode', $type);
    }

    public function scopeStatut(Builder $query, string $statut): Builder
    {
        return $query->where('statut', $statut);
    }

    public function scopeStatuts(Builder $query, array $statuts): Builder
    {
        return $query->whereIn('statut', $statuts);
    }

    public function scopePaye(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PAYE);
    }

    public function scopePartiel(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PARTIEL);
    }

    public function scopeImpaye(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_IMPAYE);
    }

    public function scopeSurpaye(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_SURPAYE);
    }

    public function scopeMensuel(Builder $query): Builder
    {
        return $query->where('type_periode', self::MODE_MENSUEL);
    }

    public function scopeTranche(Builder $query): Builder
    {
        return $query->where('type_periode', self::MODE_TRANCHE);
    }

    /**
     * Paiements non soldés (impayé ou partiel).
     */
    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->whereIn('statut', self::STATUTS_EN_ATTENTE);
    }

    /**
     * Paiements soldés (payé ou surpayé).
     */
    public function scopeSoldes(Builder $query): Builder
    {
        return $query->whereIn('statut', self::STATUTS_SOLDES);
    }

    /**
     * Eager-loading helper : évite d'oublier `->with('eleve')` partout.
     */
    public function scopeWithEleve(Builder $query): Builder
    {
        return $query->with(['eleve:id,nom,prenom,postnom,sexe']);
    }

    /**
     * Sélection sécurisée : garantit que `eleve_id` est toujours présent
     * pour ne pas casser le eager-loading de `eleve`.
     */
    public function scopeSelectSafe(Builder $query, array $columns = ['*']): Builder
    {
        if ($columns === ['*']) {
            return $query->select('*');
        }

        if (!in_array('eleve_id', $columns, true)) {
            $columns[] = 'eleve_id';
        }

        return $query->select($columns);
    }

    /**
     * Recherche par nom/prénom/postnom d'élève.
     */
    public function scopeRecherche(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->whereHas('eleve', function (Builder $q) use ($search): void {
            $q->where(function (Builder $inner) use ($search): void {
                $inner->where('nom', 'LIKE', "%{$search}%")
                      ->orWhere('prenom', 'LIKE', "%{$search}%")
                      ->orWhere('postnom', 'LIKE', "%{$search}%");
            });
        });
    }

    /**
     * Filtre par plage de dates de paiement.
     */
    public function scopeEntreDates(Builder $query, ?string $debut, ?string $fin): Builder
    {
        if ($debut) {
            $query->where('date_paiement', '>=', $debut);
        }

        if ($fin) {
            $query->where('date_paiement', '<=', $fin);
        }

        return $query;
    }

    // ============================================================
    // ACCESSORS — Montants formatés
    // ============================================================

    protected function montantAttenduUsdFormate(): Attribute
    {
        return Attribute::get(fn (): string => number_format((float) $this->montant_attendu_usd, 0, ',', ' '));
    }

    protected function montantPayeUsdFormate(): Attribute
    {
        return Attribute::get(fn (): string => number_format((float) $this->montant_paye_usd, 0, ',', ' '));
    }

    protected function montantRestantUsdFormate(): Attribute
    {
        return Attribute::get(fn (): string => number_format((float) $this->montant_restant_usd, 0, ',', ' '));
    }

    protected function montantAttenduFcFormate(): Attribute
    {
        return Attribute::get(fn (): string => number_format((float) $this->montant_attendu_fc, 0, ',', ' '));
    }

    protected function montantPayeFcFormate(): Attribute
    {
        return Attribute::get(fn (): string => number_format((float) $this->montant_paye_fc, 0, ',', ' '));
    }

    protected function montantRestantFcFormate(): Attribute
    {
        return Attribute::get(fn (): string => number_format((float) $this->montant_restant_fc, 0, ',', ' '));
    }

    /**
     * Pourcentage payé (0 à 100).
     */
    protected function pourcentagePaye(): Attribute
    {
        return Attribute::get(function (): float {
            $attendu = (float) $this->montant_attendu_usd;

            if ($attendu <= 0) {
                return 0.0;
            }

            return min(100.0, round(((float) $this->montant_paye_usd / $attendu) * 100, 1));
        });
    }

    /**
     * Libellé lisible du statut.
     */
    protected function statutLibelle(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->statut) {
            self::STATUT_PAYE    => 'Payé',
            self::STATUT_PARTIEL => 'Partiel',
            self::STATUT_SURPAYE => 'Surpayé',
            default              => 'Impayé',
        });
    }

    // ============================================================
    // MÉTHODES MÉTIER — Statut
    // ============================================================

    public function estPaye(): bool
    {
        return $this->statut === self::STATUT_PAYE;
    }

    public function estPartiel(): bool
    {
        return $this->statut === self::STATUT_PARTIEL;
    }

    public function estImpaye(): bool
    {
        return $this->statut === self::STATUT_IMPAYE;
    }

    public function estSurpaye(): bool
    {
        return $this->statut === self::STATUT_SURPAYE;
    }

    public function estEnAttente(): bool
    {
        return in_array($this->statut, self::STATUTS_EN_ATTENTE, true);
    }

    public function estSolde(): bool
    {
        return in_array($this->statut, self::STATUTS_SOLDES, true);
    }

    // ============================================================
    // MÉTHODES MÉTIER — Calculs
    // ============================================================

    /**
     * Recalcule les montants restants et met à jour le statut.
     * Appelé automatiquement au `saving`.
     */
    public function recalculerMontants(): void
    {
        $attenduUsd = (float) $this->montant_attendu_usd;
        $payeUsd    = (float) $this->montant_paye_usd;

        $this->montant_restant_usd = max(0, $attenduUsd - $payeUsd);

        // Recalcul FC uniquement si l'un des deux champs est renseigné
        if ($this->montant_attendu_fc !== null || $this->montant_paye_fc !== null) {
            $attenduFc = (float) ($this->montant_attendu_fc ?? 0);
            $payeFc    = (float) ($this->montant_paye_fc ?? 0);

            $this->montant_restant_fc = max(0, $attenduFc - $payeFc);
        }

        $this->statut = $this->determinerStatut();
    }

    /**
     * Détermine le statut en fonction des montants attendu/payé.
     */
    public function determinerStatut(): string
    {
        $attendu = (float) $this->montant_attendu_usd;
        $paye    = (float) $this->montant_paye_usd;

        // Cas dégénéré : rien d'attendu
        if ($attendu <= 0) {
            return $paye > 0 ? self::STATUT_SURPAYE : self::STATUT_IMPAYE;
        }

        if ($paye >= $attendu) {
            return $paye > $attendu ? self::STATUT_SURPAYE : self::STATUT_PAYE;
        }

        return $paye > 0 ? self::STATUT_PARTIEL : self::STATUT_IMPAYE;
    }

    /**
     * Ajoute un montant payé au paiement existant et sauvegarde.
     */
    public function ajouterPaiement(float $montantUsd, ?float $montantFc = null): self
    {
        $this->montant_paye_usd = (float) $this->montant_paye_usd + $montantUsd;

        if ($montantFc !== null) {
            $this->montant_paye_fc = (float) $this->montant_paye_fc + $montantFc;
        }

        $this->date_paiement = now()->toDateString();
        $this->save();

        return $this;
    }

    // ============================================================
    // HELPERS
    // ============================================================

    /**
     * Retourne true si le paiement est soldé (payé ou surpayé).
     */
    public function isSolde(): bool
    {
        return $this->estSolde();
    }

    /**
     * Retourne true si le paiement est en retard.
     */
    public function estEnRetard(): bool
    {
        return $this->estEnAttente()
            && $this->date_paiement === null;
    }

    /* ============================================================
       🚨 SUPPRIMÉ — NE PAS RECRÉER CET ACCESSOR
       ============================================================
       
       L'accessor `getEleveIdAttribute(): ?int` a été SUPPRIMÉ car :
       
       1. Il était REDONDANT : `$casts['eleve_id'] = 'integer'` fait déjà 
          la conversion automatiquement.
       
       2. Il CAUSAIT l'erreur :
          `Return value must be of type ?int, string returned`
          → avec `declare(strict_types=1)`, PHP refuse de retourner 
            un string `"546"` depuis une méthode typée `?int`.
       
       3. La solution propre = laisser `$casts` faire son travail.
       
       Si tu veux VRAIMENT garder un accessor défensif (non recommandé), 
       il faut caster explicitement :
       
           public function getEleveIdAttribute(): ?int
           {
               $value = $this->attributes['eleve_id'] ?? null;
               return $value === null ? null : (int) $value;
           }
       
       Mais la solution actuelle (sans accessor) est préférable.
    ============================================================ */
}