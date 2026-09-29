<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Centralise la gestion du cache de la page d'accueil.
 *
 * ⚠️ Clés versionnées (`_v1`, `_v2`…) pour pouvoir les invalider
 *    en changeant simplement la constante sans migration.
 */
final class HomeCacheService
{
    /** Clé du cache contenant les données agrégées de la home. */
    public const KEY = 'home_data_v1';

    /** TTL en secondes (1 heure). */
    public const TTL = 3600;

    /**
     * Invalide le cache de la home.
     */
    public static function flush(): void
    {
        Cache::forget(self::KEY);
    }

    /**
     * Récupère les données de la home avec mise en cache.
     *
     * @template T
     * @param  callable():T  $callback
     * @return T
     */
    public static function remember(callable $callback): mixed
    {
        return Cache::remember(self::KEY, self::TTL, $callback);
    }
}