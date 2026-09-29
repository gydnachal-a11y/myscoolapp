<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RemboursementAvance
 *
 * Représente une ligne de remboursement d'une avance lors d'un paiement de salaire.
 * Le taux de change est stocké pour garantir l'intégrité des montants en FC lors des annulations.
 *
 * @property int $id
 * @property int $avance_id
 * @property int|null $paiement_salaire_id
 * @property float $montant_rembourse_usd
 * @property float $montant_rembourse_fc
 * @property float $taux_change
 * @property \Carbon\Carbon $date_remboursement
 * @property string|null $commentaire
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read AvanceSalaire $avance
 * @property-read PaiementSalaire|null $paiementSalaire
 */
class RemboursementAvance extends Model
{
    use HasFactory;

    // ============================================================
    // ATTRIBUTS
    // ============================================================

    protected $table = 'remboursements_avances';

    protected $fillable = [
        'avance_id',
        'paiement_salaire_id',
        'montant_rembourse_usd',
        'montant_rembourse_fc',
        'taux_change',
        'date_remboursement',
        'commentaire',
    ];

    protected $casts = [
        'date_remboursement'    => 'date',
        'montant_rembourse_usd' => 'float',
        'montant_rembourse_fc'  => 'float',
        'taux_change'           => 'float',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    /**
     * L'avance associée à ce remboursement.
     */
    public function avance(): BelongsTo
    {
        return $this->belongsTo(AvanceSalaire::class, 'avance_id');
    }

    /**
     * Le paiement de salaire qui a déclenché ce remboursement.
     */
    public function paiementSalaire(): BelongsTo
    {
        return $this->belongsTo(PaiementSalaire::class, 'paiement_salaire_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Filtre les remboursements liés à un paiement de salaire.
     */
    public function scopeForPaiement(Builder $query, PaiementSalaire|int $paiement): Builder
    {
        $paiementId = $paiement instanceof PaiementSalaire ? $paiement->id : $paiement;

        return $query->where('paiement_salaire_id', $paiementId);
    }

    /**
     * Filtre les remboursements d'une avance spécifique.
     */
    public function scopeForAvance(Builder $query, AvanceSalaire|int $avance): Builder
    {
        $avanceId = $avance instanceof AvanceSalaire ? $avance->id : $avance;

        return $query->where('avance_id', $avanceId);
    }

    /**
     * Remboursements manuels (sans paiement de salaire associé).
     */
    public function scopeManuels(Builder $query): Builder
    {
        return $query->whereNull('paiement_salaire_id');
    }

    /**
     * Remboursements automatiques (déclenchés par un paiement de salaire).
     */
    public function scopeAutomatiques(Builder $query): Builder
    {
        return $query->whereNotNull('paiement_salaire_id');
    }

    /**
     * Remboursements sur une période donnée.
     */
    public function scopeEntreDates(Builder $query, string $debut, string $fin): Builder
    {
        return $query->whereBetween('date_remboursement', [$debut, $fin]);
    }

    /**
     * Remboursements récents (par défaut : 30 derniers jours).
     */
    public function scopeRecents(Builder $query, int $jours = 30): Builder
    {
        return $query->where('date_remboursement', '>=', now()->subDays($jours));
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * Indique si ce remboursement a été généré automatiquement.
     */
    public function getEstAutomatiqueAttribute(): bool
    {
        return $this->paiement_salaire_id !== null;
    }

    /**
     * Indique si ce remboursement est manuel.
     */
    public function getEstManuelAttribute(): bool
    {
        return $this->paiement_salaire_id === null;
    }

    /**
     * Montant remboursé formaté en USD.
     */
    public function getMontantUsdFormateAttribute(): string
    {
        return number_format((float) $this->montant_rembourse_usd, 0, ',', ' ') . ' $';
    }

    /**
     * Montant remboursé formaté en FC.
     */
    public function getMontantFcFormateAttribute(): string
    {
        return number_format((float) $this->montant_rembourse_fc, 0, ',', ' ') . ' FC';
    }

    /**
     * Date de remboursement formatée.
     */
    public function getDateFormateeAttribute(): string
    {
        return $this->date_remboursement?->translatedFormat('d M Y') ?? '—';
    }

    // ============================================================
    // MÉTHODES MÉTIER
    // ============================================================

    /**
     * Vérifie si ce remboursement peut être annulé.
     * Un remboursement ne peut être annulé que s'il est automatique
     * et lié à un paiement de salaire.
     */
    public function peutEtreAnnule(): bool
    {
        return $this->est_automatique && $this->paiementSalaire !== null;
    }

    // ============================================================
    // BOOT — ÉVÉNEMENTS
    // ============================================================

    protected static function booted(): void
    {
        // Invalide le cache de dette de l'utilisateur lors de toute modification
        $invaliderCache = function (self $remboursement) {
            $userId = $remboursement->avance?->user_id;
            if ($userId) {
                cache()->forget('dettes_actives_user_' . $userId);
            }
        };

        static::saved($invaliderCache);
        static::deleted($invaliderCache);
    }
}