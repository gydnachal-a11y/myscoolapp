<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    // ============================================================
    // CONFIGURATION
    // ============================================================

    protected $table = 'newsletter_subscribers';

    protected $fillable = [
        'email',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'id',   // masqué si exposé en JSON (protection)
    ];

    protected $appends = [
        'initial',
        'masked_email',
        'is_recent',
    ];

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeOlderThan(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '<', now()->subDays($days));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        // Échappement LIKE
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->where('email', 'like', "%{$escaped}%");
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * Initiale pour avatar (UTF-8 safe).
     */
    protected function initial(): Attribute
    {
        return Attribute::get(function (): string {
            $email = (string) $this->email;

            return $email === ''
                ? '?'
                : mb_strtoupper(mb_substr($email, 0, 1));
        });
    }

    /**
     * Email masqué pour l'affichage public.
     * Ex: "jean.dupont@example.com" → "j***t@example.com"
     */
    protected function maskedEmail(): Attribute
    {
        return Attribute::get(function (): string {
            $email = (string) $this->email;

            if (! str_contains($email, '@')) {
                return $email;
            }

            [$local, $domain] = explode('@', $email, 2);

            if (mb_strlen($local) <= 2) {
                return '*@' . $domain;
            }

            return mb_substr($local, 0, 1)
                . str_repeat('*', max(1, mb_strlen($local) - 2))
                . mb_substr($local, -1)
                . '@' . $domain;
        });
    }

    /**
     * L'abonné s'est-il inscrit récemment (< 7 jours) ?
     */
    protected function isRecent(): Attribute
    {
        return Attribute::get(fn (): bool => $this->created_at?->gt(now()->subDays(7)) ?? false);
    }

    // ============================================================
    // HELPERS MÉTIER
    // ============================================================

    public function getDomain(): string
    {
        $email = (string) $this->email;

        if (! str_contains($email, '@')) {
            return '';
        }

        return mb_strtolower(explode('@', $email, 2)[1]);
    }

    /**
     * Nombre de jours depuis l'inscription.
     */
    public function getAgeInDays(): int
    {
        return $this->created_at
            ? (int) $this->created_at->diffInDays(now())
            : 0;
    }

    // ============================================================
    // EVENTS
    // ============================================================

    protected static function booted(): void
    {
        static::creating(function (self $subscriber): void {
            // ✅ Normalisation : trim + lowercase
            if ($subscriber->email !== null) {
                $subscriber->email = mb_strtolower(trim($subscriber->email));
            }
        });

        static::created(function (self $subscriber): void {
            Log::info('Newsletter: nouvel abonné', [
                'subscriber_id' => $subscriber->id,
                'email_hash'    => self::hashEmail($subscriber->email),
                'domain'        => $subscriber->getDomain(),
            ]);
        });

        static::deleted(function (self $subscriber): void {
            Log::info('Newsletter: abonné supprimé', [
                'subscriber_id' => $subscriber->id,
                'email_hash'    => self::hashEmail($subscriber->email),
            ]);
        });
    }

    // ============================================================
    // HELPERS PRIVÉS
    // ============================================================

    private static function hashEmail(?string $email): string
    {
        if (! $email) {
            return '';
        }

        return substr(hash('sha256', mb_strtolower($email)), 0, 16);
    }
}