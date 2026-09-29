<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class PermissionCacheService
{
    public const PREFIX_USER_PERMS = 'user_all_permissions_';
    public const KEY_PERMISSIONS   = 'permissions_grouped_v1';
    public const KEY_RESOURCES     = 'permissions_resources_v1';

    public function forgetUser(int $userId): void
    {
        Cache::forget(self::PREFIX_USER_PERMS . $userId);
        Cache::forget("user_permissions_{$userId}"); // legacy
        Cache::forget("user_roles_{$userId}");       // legacy
    }

    public function forgetUsers(array $userIds): void
    {
        $ids = array_unique(array_filter($userIds, fn ($id) => (int) $id > 0));

        foreach ($ids as $id) {
            $this->forgetUser((int) $id);
        }

        if (! empty($ids)) {
            Log::debug('Caches utilisateurs invalidés', ['count' => count($ids)]);
        }
    }

    public function forgetAllUsers(): int
    {
        $count = 0;

        User::query()
            ->select('id')
            ->chunkById(500, function ($users) use (&$count): void {
                foreach ($users as $user) {
                    $this->forgetUser((int) $user->id);
                    $count++;
                }
            });

        return $count;
    }

    public function flushGlobals(): void
    {
        Cache::forget(self::KEY_PERMISSIONS);
        Cache::forget(self::KEY_RESOURCES);
    }
}