<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Devise extends Model
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    public const CODE_USD        = 'USD';
    public const CODE_CDF        = 'CDF';
    public const TAUX_PAR_DEFAUT = 2800.0;
    private const CACHE_TTL      = 3600; // 1h

    // ============================================================
    // CONFIGURATION
    // ============================================================

    protected $fillable = [
        'code',
        'nom',
        'symbole',
        'est_defaut',
    ];

    protected $casts = [
        'est_defaut' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'label',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function tauxSources(): HasMany
    {
        return $this->hasMany(TauxChange::class, 'devise_source_id');
    }

    public function tauxCibles(): HasMany
    {
        return $this->hasMany(TauxChange::class, 'devise_cible_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeDefaut(Builder $query): Builder
    {
        return $query->where('est_defaut', true);
    }

    public function scopeCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper($code));
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * Label lisible : "USD — Dollar américain".
     */
    protected function label(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(
            fn (): string => trim(($this->code ?? '') . ' — ' . ($this->nom ?? ''), ' —')
        );
    }

    // ============================================================
    // MÉTHODES MÉTIER
    // ============================================================

    /**
     * Taux de conversion de cette devise vers une autre.
     *
     * ✅ Cache 1h — évite 3 requêtes à chaque appel
     * ✅ Fallback intelligent : si pas de taux trouvé, on tente le taux inverse
     * ✅ Fallback final : TAUX_PAR_DEFAUT (2800)
     */
    public function tauxVers(self $cible): float
    {
        // Cas trivial : même devise
        if ($this->id === $cible->id) {
            return 1.0;
        }

        $cacheKey = "taux_change:{$this->code}_to_{$cible->code}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cible): float {
            // 1. Taux direct
            $direct = $this->tauxSources()
                ->where('devise_cible_id', $cible->id)
                ->value('taux');

            if ($direct !== null && (float) $direct > 0) {
                return (float) $direct;
            }

            // 2. Taux inverse (division)
            $inverse = $cible->tauxSources()
                ->where('devise_cible_id', $this->id)
                ->value('taux');

            if ($inverse !== null && (float) $inverse > 0) {
                return round(1 / (float) $inverse, 6);
            }

            // 3. Fallback USD ↔ CDF
            if (
                ($this->code === self::CODE_USD && $cible->code === self::CODE_CDF) ||
                ($this->code === self::CODE_CDF && $cible->code === self::CODE_USD)
            ) {
                return self::TAUX_PAR_DEFAUT;
            }

            // 4. Fallback global
            return 1.0;
        });
    }

    /**
     * Raccourci statique : taux USD → CDF avec cache.
     */
    public static function tauxUsdVersCdf(): float
    {
        return Cache::remember('taux_change_usd_cdf', self::CACHE_TTL, function (): float {
            try {
                $usd = self::query()->code(self::CODE_USD)->first();
                $cdf = self::query()->code(self::CODE_CDF)->first();

                if ($usd && $cdf) {
                    return $usd->tauxVers($cdf);
                }
            } catch (\Throwable $e) {
                \Log::warning('Impossible de charger le taux USD→CDF', [
                    'error' => $e->getMessage(),
                ]);
            }

            return self::TAUX_PAR_DEFAUT;
        });
    }

    /**
     * Rafraîchit le cache des taux (à appeler après un update).
     */
    public static function viderCacheTaux(): void
    {
        Cache::forget('taux_change_usd_cdf');
        Cache::forget('taux_change');
        Cache::forget('taux_change:USD_to_CDF');
        Cache::forget('taux_change:CDF_to_USD');
    }
}