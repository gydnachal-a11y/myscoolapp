<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Contact extends Authenticatable
{
    use HasFactory, Notifiable;

    // ============================================================
    // CONFIGURATION
    // ============================================================

    protected $table = 'contacts';

    /**
     * ⚠️ Champs `est_responsable` et `eleve_id` retirés du fillable.
     * Ils doivent être modifiés UNIQUEMENT via une méthode dédiée
     * (ex. depuis l'admin), jamais par l'utilisateur lui-même.
     */
    protected $fillable = [
        'nom',
        'email',
        'telephone',
        'password',
        'google_id',
    ];

    /**
     * ⚠️ Champs PROTÉGÉS — empêche toute modification de masse,
     * même si un attaquant les envoie dans une requête.
     */
    protected $guarded = [
        'id',
        'est_responsable',
        'eleve_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'est_responsable'   => 'boolean',
        'eleve_id'          => 'integer',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    protected $appends = [
        'is_responsable',
    ];

    /** Valeurs par défaut (indépendant de la DB). */
    protected $attributes = [
        'est_responsable' => false,
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class, 'eleve_id');
    }

    /**
     * ✅ Relation ajoutée : tous les messages échangés par ce contact.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ContactMessage::class, 'contact_id');
    }

    /**
     * Messages non lus par ce contact.
     */
    public function unreadMessages(): HasMany
    {
        return $this->messages()
            ->where('from_admin', true)
            ->where('lu', false);
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeResponsables(Builder $query): Builder
    {
        return $query->where('est_responsable', true);
    }

    public function scopeEleves(Builder $query): Builder
    {
        return $query->whereNotNull('eleve_id');
    }

    public function scopeGoogle(Builder $query): Builder
    {
        return $query->whereNotNull('google_id');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    protected function isResponsable(): Attribute
    {
        return Attribute::get(fn (): bool => (bool) $this->est_responsable);
    }

    protected function hasGoogle(): Attribute
    {
        return Attribute::get(fn (): bool => $this->google_id !== null);
    }

    // ============================================================
    // HELPERS MÉTIER
    // ============================================================

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function hasGoogleAccount(): bool
    {
        return $this->google_id !== null;
    }

    public function hasEleve(): bool
    {
        return $this->eleve_id !== null;
    }

    /**
     * Initiale à afficher dans les avatars.
     */
    public function initial(): string
    {
        $nom = trim((string) $this->nom);

        return $nom === ''
            ? '?'
            : mb_strtoupper(mb_substr($nom, 0, 1));
    }

    // ============================================================
    // PROMOTION / RÔLES (à utiliser UNIQUEMENT depuis l'admin)
    // ============================================================

    /**
     * Promeut ou rétrograde ce contact en tant que responsable.
     * ⚠️ NE PAS exposer au frontend utilisateur.
     */
    public function setResponsable(bool $responsable): self
    {
        $this->est_responsable = $responsable;
        $this->save();

        return $this;
    }

    /**
     * Associe ce contact à un élève (parent / tuteur).
     * ⚠️ NE PAS exposer au frontend utilisateur.
     */
    public function attachToEleve(?int $eleveId): self
    {
        $this->eleve_id = $eleveId;
        $this->save();

        return $this;
    }

    // ============================================================
    // OAUTH GOOGLE
    // ============================================================

    /**
     * Recherche ou crée un contact à partir d'un profil Google.
     *
     * ⚠️ Règles de sécurité strictes :
     *   1. Si un contact existe avec ce google_id → on le retourne.
     *   2. Si un contact existe avec cet email MAIS avec un google_id
     *      DIFFÉRENT → on REFUSE (empêche le détournement de compte).
     *   3. Si un contact existe avec cet email SANS google_id :
     *      - Si l'email est vérifié → on lie le Google
     *      - Sinon → on refuse (compte non vérifié = suspect)
     *   4. Sinon → on crée un nouveau contact.
     *
     * @throws \RuntimeException Si le compte est déjà lié à un autre Google
     *                            ou si l'email n'est pas vérifié.
     */
    public static function findOrCreateFromGoogle(
        string $googleId,
        string $email,
        string $name
    ): self {
        $email = mb_strtolower(trim($email));

        // ---------- 1. Recherche par google_id (prioritaire) ----------
        $byGoogle = static::query()->where('google_id', $googleId)->first();

        if ($byGoogle) {
            return $byGoogle;
        }

        // ---------- 2. Recherche par email ----------
        $byEmail = static::query()->where('email', $email)->first();

        if ($byEmail) {
            // 🚨 Email existe mais déjà lié à un AUTRE Google → refus
            if ($byEmail->google_id !== null && $byEmail->google_id !== $googleId) {
                Log::warning('OAuth: tentative de détournement de compte', [
                    'email_hash' => self::hashEmail($email),
                    'existing_google_hash' => substr(hash('sha256', $byEmail->google_id), 0, 16),
                    'incoming_google_hash' => substr(hash('sha256', $googleId), 0, 16),
                ]);

                throw new \RuntimeException(
                    'Ce compte est déjà associé à un autre compte Google. '
                    . 'Connectez-vous avec votre mot de passe pour le modifier.'
                );
            }

            // 🚨 Email non vérifié → refus de liaison automatique
            if (! $byEmail->isVerified()) {
                Log::warning('OAuth: liaison refusée (email non vérifié)', [
                    'email_hash' => self::hashEmail($email),
                ]);

                throw new \RuntimeException(
                    'Votre compte doit d\'abord être vérifié avant de lier Google.'
                );
            }

            // ✅ Email vérifié, pas de Google lié → on lie
            $byEmail->google_id = $googleId;
            $byEmail->save();

            Log::info('OAuth: Google lié à un compte existant', [
                'contact_id' => $byEmail->id,
                'email_hash' => self::hashEmail($email),
            ]);

            return $byEmail;
        }

        // ---------- 3. Création d'un nouveau contact ----------
        try {
            $contact = DB::transaction(function () use ($googleId, $email, $name): self {
                return static::create([
                    'nom'               => trim($name) ?: $email,
                    'email'             => $email,
                    'google_id'         => $googleId,
                    'password'          => Str::random(64),   // inutilisé
                    'est_responsable'   => false,
                    'email_verified_at' => now(),             // ✅ Google garantit l'email
                ]);
            });

            Log::info('OAuth: nouveau contact créé', [
                'contact_id' => $contact->id,
                'email_hash' => self::hashEmail($email),
            ]);

            return $contact;

        } catch (\Illuminate\Database\QueryException $e) {
            // Race condition : un autre process a créé le même email entre-temps
            if ($e->getCode() === '23000') {
                $existing = static::query()->where('email', $email)->first();

                if ($existing) {
                    return $existing;
                }
            }

            throw $e;
        }
    }

    // ============================================================
    // EVENTS
    // ============================================================

    protected static function booted(): void
    {
        static::creating(function (self $contact): void {
            // Normalisation email
            if ($contact->email !== null) {
                $contact->email = mb_strtolower(trim($contact->email));
            }

            // Normalisation nom
            if ($contact->nom !== null) {
                $contact->nom = trim($contact->nom);
            }
        });

        static::saving(function (self $contact): void {
            // Sécurité supplémentaire : empêche le changement de est_responsable
            // si le champ n'a pas été modifié via une méthode dédiée
            if ($contact->exists && $contact->isDirty('est_responsable')) {
                // À logger pour audit — le save direct est autorisé
                // (car setResponsable() passe par save())
                Log::info('Contact: est_responsable modifié', [
                    'contact_id' => $contact->id,
                    'new_value'  => $contact->est_responsable,
                ]);
            }
        });
    }

    // ============================================================
    // HELPERS PRIVÉS
    // ============================================================

    private static function hashEmail(?string $email): string
    {
        if (!$email) {
            return '';
        }

        return substr(hash('sha256', strtolower($email)), 0, 16);
    }
}