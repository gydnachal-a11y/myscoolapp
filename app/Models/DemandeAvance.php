<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeAvance extends Model
{
    // ============================================================
    // CONFIGURATION
    // ============================================================

    /**
     * Nom exact de la table (Laravel déduirait "demande_avances" par défaut,
     * on le déclare explicitement pour la lisibilité).
     */
    protected $table = 'demande_avances';

    // ============================================================
    // CONSTANTES — STATUTS
    // ============================================================

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDEE    = 'validee';
    public const STATUT_REFUSEE    = 'refusee';

    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_VALIDEE,
        self::STATUT_REFUSEE,
    ];

    // ============================================================
    // ATTRIBUTS
    // ============================================================

    protected $fillable = [
        'user_id',
        'session_avance_id',
        'montant_demande_usd',
        'montant_demande_fc',
        'taux_applique',
        'motif',
        'statut',
        'motif_refus',
        'traite_par',
        'traite_le',
        'avance_id',
        'notifiee_vue_a',   // ✅ NOUVEAU — tracking notification
    ];

    protected $casts = [
        'montant_demande_usd' => 'decimal:2',
        'montant_demande_fc'  => 'decimal:2',
        'taux_applique'       => 'decimal:2',
        'traite_le'           => 'datetime',
        'notifiee_vue_a'      => 'datetime',   // ✅ NOUVEAU
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionAvance::class, 'session_avance_id');
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function avance(): BelongsTo
    {
        return $this->belongsTo(AvanceSalaire::class, 'avance_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeEnAttente(Builder $q): Builder
    {
        return $q->where('statut', self::STATUT_EN_ATTENTE);
    }

    public function scopeValidee(Builder $q): Builder
    {
        return $q->where('statut', self::STATUT_VALIDEE);
    }

    public function scopeRefusee(Builder $q): Builder
    {
        return $q->where('statut', self::STATUT_REFUSEE);
    }

    public function scopeTraitee(Builder $q): Builder
    {
        return $q->whereIn('statut', [self::STATUT_VALIDEE, self::STATUT_REFUSEE]);
    }

    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopeForSession(Builder $q, int $sessionId): Builder
    {
        return $q->where('session_avance_id', $sessionId);
    }

    public function scopeRecentes(Builder $q, int $jours = 30): Builder
    {
        return $q->where('created_at', '>=', now()->subDays($jours));
    }

    /**
     * ✅ NOUVEAU — Demandes dont la notification n'a pas encore été vue.
     * Utilisé par la cloche du dashboard.
     */
    public function scopeNotifieeNonVue(Builder $q): Builder
    {
        return $q->whereNull('notifiee_vue_a');
    }

    /**
     * ✅ NOUVEAU — Demandes dont la notification a été vue.
     */
    public function scopeNotifieeVue(Builder $q): Builder
    {
        return $q->whereNotNull('notifiee_vue_a');
    }

    // ============================================================
    // MÉTHODES DE STATUT
    // ============================================================

    public function isEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function isValidee(): bool
    {
        return $this->statut === self::STATUT_VALIDEE;
    }

    public function isRefusee(): bool
    {
        return $this->statut === self::STATUT_REFUSEE;
    }

    public function isTraitee(): bool
    {
        return $this->isValidee() || $this->isRefusee();
    }

    public function peutEtreTraitee(): bool
    {
        return $this->isEnAttente();
    }

    // ============================================================
    // ✅ NOUVEAU — GESTION DES NOTIFICATIONS
    // ============================================================

    /**
     * La notification a-t-elle été vue par le membre ?
     */
    public function isNotifieeVue(): bool
    {
        return $this->notifiee_vue_a !== null;
    }

    /**
     * La notification n'a pas encore été vue.
     */
    public function isNotifieeNonVue(): bool
    {
        return $this->notifiee_vue_a === null;
    }

    /**
     * Marque la notification comme vue (éteint la cloche pour cette demande).
     *
     * @return bool true si mise à jour, false si déjà vue
     */
    public function marquerNotifieeVue(): bool
    {
        if ($this->notifiee_vue_a !== null) {
            return false;
        }

        return $this->update([
            'notifiee_vue_a' => now(),
        ]);
    }

    /**
     * Marque en masse toutes les demandes d'un utilisateur comme vues.
     * Appelé à l'ouverture de la page des demandes.
     *
     * @return int Nombre de lignes affectées
     */
    public static function marquerToutesNotifieesVuesPour(int $userId): int
    {
        return static::query()
            ->forUser($userId)
            ->notifieeNonVue()
            ->update([
                'notifiee_vue_a' => now(),
                'updated_at'     => now(),
            ]);
    }

    /**
     * Compte les notifications non vues pour un utilisateur.
     */
    public static function countNotifieesNonVuesPour(int $userId): int
    {
        return static::query()
            ->forUser($userId)
            ->notifieeNonVue()
            ->count();
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_VALIDEE    => 'Validée',
            self::STATUT_REFUSEE    => 'Refusée',
            default                 => ucfirst((string) $this->statut),
        };
    }

    public function getStatutKeyAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'warning',
            self::STATUT_VALIDEE    => 'success',
            self::STATUT_REFUSEE    => 'danger',
            default                 => 'neutral',
        };
    }

    public function getStatutIconAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE => 'fa-hourglass-half',
            self::STATUT_VALIDEE    => 'fa-circle-check',
            self::STATUT_REFUSEE    => 'fa-circle-xmark',
            default                 => 'fa-circle',
        };
    }

    public function getMontantDemandeFcCalculeAttribute(): float
    {
        if ($this->montant_demande_fc) {
            return (float) $this->montant_demande_fc;
        }

        return round((float) $this->montant_demande_usd * (float) $this->taux_applique, 2);
    }

    public function getDelaiTraitementHeuresAttribute(): ?float
    {
        if (!$this->traite_le) {
            return null;
        }

        return round($this->created_at->diffInHours($this->traite_le), 1);
    }

    public function getTraiteeRapidementAttribute(): ?bool
    {
        $delai = $this->delai_traitement_heures;

        return $delai !== null ? $delai < 24 : null;
    }

    // ============================================================
    // MÉTHODES MÉTIER
    // ============================================================

    public function peutEtreValidee(): bool
    {
        return $this->isEnAttente() && $this->user !== null;
    }

    public function peutEtreRefusee(): bool
    {
        return $this->isEnAttente();
    }

    /**
     * Marque la demande comme validée.
     * ✅ Réinitialise `notifiee_vue_a` pour que le membre soit re-notifié.
     */
    public function marquerValidee(int $avanceId, ?int $traiteParId = null): bool
    {
        return $this->update([
            'statut'         => self::STATUT_VALIDEE,
            'avance_id'      => $avanceId,
            'traite_par'     => $traiteParId ?? auth()->id(),
            'traite_le'      => now(),
            'notifiee_vue_a' => null,   // ✅ Re-notifie le membre
        ]);
    }

    /**
     * Marque la demande comme refusée avec un motif.
     * ✅ Réinitialise `notifiee_vue_a` pour que le membre soit re-notifié.
     */
    public function marquerRefusee(string $motif, ?int $traiteParId = null): bool
    {
        return $this->update([
            'statut'         => self::STATUT_REFUSEE,
            'motif_refus'    => $motif,
            'traite_par'     => $traiteParId ?? auth()->id(),
            'traite_le'      => now(),
            'notifiee_vue_a' => null,   // ✅ Re-notifie le membre
        ]);
    }

    // ============================================================
    // BOOT — ÉVÉNEMENTS
    // ============================================================

    protected static function booted(): void
    {
        static::saved(function (self $demande): void {
            // Invalide le cache de dette
            cache()->forget('dettes_actives_user_' . $demande->user_id);

            // ✅ Invalide le cache du compteur de notifications
            cache()->forget('notifs_non_vues_user_' . $demande->user_id);
        });

        static::deleted(function (self $demande): void {
            cache()->forget('dettes_actives_user_' . $demande->user_id);
            cache()->forget('notifs_non_vues_user_' . $demande->user_id);
        });
    }
}