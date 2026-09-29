<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class ContactMessage extends Model
{
    use HasFactory;

    // ============================================================
    // CONSTANTES
    // ============================================================

    public const ORIGIN_ADMIN     = 'admin';
    public const ORIGIN_CONTACT   = 'contact';
    public const ORIGIN_ANONYMOUS = 'anonymous';

    /** Longueur maximale d'un message (aligné sur la validation du contrôleur). */
    public const MAX_MESSAGE_LENGTH = 10000;

    /** Sujet par défaut d'une réponse admin. */
    public const DEFAULT_REPLY_SUBJECT = 'Réponse de l\'administration';

    // ============================================================
    // CONFIGURATION
    // ============================================================

    protected $table = 'contact_messages';

    protected $fillable = [
        'contact_id',
        'nom',
        'email',
        'sujet',
        'message',
        'lu',
        'traite',
        'from_admin',
    ];

    protected $casts = [
        'contact_id' => 'integer',
        'lu'         => 'boolean',
        'traite'     => 'boolean',
        'from_admin' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'is_from_subscriber',
        'is_from_admin',
        'origin',
    ];

    /** Valeurs par défaut au niveau du modèle (indépendant de la DB). */
    protected $attributes = [
        'lu'         => false,
        'traite'     => false,
        'from_admin' => false,
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('lu', false)->orWhereNull('lu');
        });
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('lu', true);
    }

    public function scopeUntreated(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('traite', false)->orWhereNull('traite');
        });
    }

    public function scopeTreated(Builder $query): Builder
    {
        return $query->where('traite', true);
    }

    /**
     * Messages envoyés par l'administration.
     * ✅ Gère le cas `from_admin = NULL` (traité comme non-admin).
     */
    public function scopeFromAdmin(Builder $query): Builder
    {
        return $query->where('from_admin', true);
    }

    /**
     * Messages entrants (non-admin).
     * ✅ Gère `from_admin = NULL` → considéré comme entrant.
     */
    public function scopeFromContacts(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('from_admin', false)->orWhereNull('from_admin');
        });
    }

    public function scopeFromSubscriber(Builder $query): Builder
    {
        return $query->whereNotNull('contact_id');
    }

    public function scopeFromAnonymous(Builder $query): Builder
    {
        return $query->whereNull('contact_id');
    }

    /**
     * ✅ Scope réutilisable — évite la duplication dans le contrôleur.
     */
    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * ✅ Scope pour une conversation complète, triée chronologiquement.
     */
    public function scopeConversation(Builder $query, string $email): Builder
    {
        return $query->forEmail($email)->orderBy('created_at');
    }

    // ============================================================
    // ACCESSORS (API moderne Laravel 9+)
    //
    // ⚠️ IMPORTANT : les accessors sont accessibles comme PROPRIÉTÉS.
    //     Ex : $msg->is_from_admin      (pas de parenthèses !)
    //     Ex : $msg->is_from_subscriber (pas de parenthèses !)
    //
    //     Il ne peut PAS y avoir de méthode PHP `isFromAdmin()`
    //     portant le même nom (conflit PHP).
    // ============================================================

    protected function isFromSubscriber(): Attribute
    {
        return Attribute::get(fn (): bool => $this->contact_id !== null);
    }

    protected function isFromAdmin(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->from_admin);
    }

    /**
     * Origine du message : 'admin', 'contact' ou 'anonymous'.
     */
    protected function origin(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->from_admin) {
                return self::ORIGIN_ADMIN;
            }

            return $this->contact_id !== null
                ? self::ORIGIN_CONTACT
                : self::ORIGIN_ANONYMOUS;
        });
    }

    // ============================================================
    // HELPERS MÉTIER
    // ============================================================

    public function isUnread(): bool
    {
        return ! $this->lu;
    }

    public function isUntreated(): bool
    {
        return ! $this->traite;
    }

    /**
     * Initiale à afficher dans l'avatar côté admin.
     * Fallback '?' si le nom est vide.
     */
    public function initial(): string
    {
        $nom = trim((string) $this->nom);

        return $nom === ''
            ? '?'
            : mb_strtoupper(mb_substr($nom, 0, 1));
    }

    // ============================================================
    // MUTATIONS ATOMIQUES
    // ============================================================

    /**
     * Marque le message comme lu (atomique, idempotent).
     *
     * @return bool true si la ligne a été mise à jour, false sinon
     */
    public function markAsRead(): bool
    {
        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('lu', false)
            ->update([
                'lu'         => true,
                'updated_at' => now(),
            ]);

        if ($updated) {
            $this->setAttribute('lu', true);
            $this->syncOriginalAttribute('lu');
        }

        return (bool) $updated;
    }

    /**
     * Marque le message comme traité (atomique, idempotent).
     *
     * @return bool true si la ligne a été mise à jour, false sinon
     */
    public function markAsTreated(): bool
    {
        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('traite', false)
            ->update([
                'traite'     => true,
                'updated_at' => now(),
            ]);

        if ($updated) {
            $this->setAttribute('traite', true);
            $this->syncOriginalAttribute('traite');
        }

        return (bool) $updated;
    }

    /**
     * Marque comme lu ET traité en 1 requête.
     *
     * @return bool true si quelque chose a changé
     */
    public function markAsReadAndTreated(): bool
    {
        $updated = static::query()
            ->whereKey($this->getKey())
            ->where(function (Builder $q): void {
                $q->where('lu', false)->orWhere('traite', false);
            })
            ->update([
                'lu'         => true,
                'traite'     => true,
                'updated_at' => now(),
            ]);

        if ($updated) {
            $this->setAttribute('lu', true);
            $this->setAttribute('traite', true);
            $this->syncOriginalAttribute('lu');
            $this->syncOriginalAttribute('traite');
        }

        return (bool) $updated;
    }

    // ============================================================
    // HELPERS STATIQUES
    // ============================================================

    /**
     * Marque en masse tous les messages entrants non lus d'une conversation.
     *
     * @return int nombre de lignes affectées
     */
    public static function markConversationRead(string $email): int
    {
        return static::query()
            ->forEmail($email)
            ->fromContacts()
            ->where('lu', false)
            ->update(['lu' => true, 'updated_at' => now()]);
    }

    /**
     * Marque en masse tous les messages non traités d'une conversation.
     *
     * @return int nombre de lignes affectées
     */
    public static function markConversationTreated(string $email): int
    {
        return static::query()
            ->forEmail($email)
            ->where('traite', false)
            ->update(['traite' => true, 'updated_at' => now()]);
    }

    /**
     * Compte les messages non lus par email.
     *
     * @param  array<int, string> $emails
     * @return array<string, int>  email => unread_count
     */
    public static function unreadCountsByEmails(array $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        return static::query()
            ->selectRaw('email, COUNT(*) as unread')
            ->whereIn('email', $emails)
            ->fromContacts()
            ->unread()
            ->groupBy('email')
            ->pluck('unread', 'email')
            ->all();
    }

    // ============================================================
    // EVENTS
    // ============================================================

    protected static function booted(): void
    {
        static::creating(function (self $message): void {
            // Sécurité : tronque les champs aux longueurs attendues
            if ($message->message !== null) {
                $message->message = mb_substr($message->message, 0, self::MAX_MESSAGE_LENGTH);
            }
        });

        static::created(function (self $message): void {
            Log::info('ContactMessage créé', [
                'id'         => $message->id,
                'email_hash' => self::hashEmail($message->email),
                'from_admin' => $message->from_admin,
            ]);
        });
    }

    // ============================================================
    // HELPERS PRIVÉS
    // ============================================================

    private static function hashEmail(?string $email): string
    {
        if ($email === null) {
            return '';
        }

        return substr(hash('sha256', strtolower($email)), 0, 16);
    }
}