<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\HomeCacheService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Vide le cache de la page d'accueil après toute modification d'un modèle
 * qui impacte son contenu (élèves, personnel, actualités, paramètres…).
 *
 * ✅ Implémente ShouldHandleEventsAfterCommit pour éviter d'invalider
 *    le cache AVANT que la transaction DB ne soit committée.
 *    → Sinon, en cas de rollback, on invalide pour rien.
 */
class HomeCacheObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Modèles écoutés : à titre indicatif, pour debug uniquement.
     * (Le nom réel du modèle est injecté par Laravel.)
     */
    public function saved(Model $model): void
    {
        $this->flush('saved', $model);
    }

    public function deleted(Model $model): void
    {
        $this->flush('deleted', $model);
    }

    public function restored(Model $model): void
    {
        $this->flush('restored', $model);
    }

    public function forceDeleted(Model $model): void
    {
        $this->flush('forceDeleted', $model);
    }

    /**
     * Invalide le cache de la page d'accueil.
     * Log silencieux : un échec de cache ne doit jamais casser une écriture.
     */
    private function flush(string $event, Model $model): void
    {
        try {
            HomeCacheService::flush();
        } catch (Throwable $e) {
            Log::warning('HomeCacheObserver: échec invalidation cache', [
                'event'      => $event,
                'model'      => $model::class,
                'model_id'   => $model->getKey(),
                'error'      => $e->getMessage(),
            ]);
        }
    }
}