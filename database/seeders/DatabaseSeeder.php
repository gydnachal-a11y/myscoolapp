<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * ⚠️ En production :
     *   - Ne JAMAIS appeler les seeders de démonstration (Faker).
     *   - Les seeders de prod doivent être IDEMPOTENTS
     *     (firstOrCreate, updateOrCreate) pour pouvoir être relancés
     *     sans créer de doublons.
     */
    public function run(): void
    {
        // =========================================================
        // 1. Seeders de PRODUCTION (exécutés partout)
        // =========================================================

        // Rôles et permissions (doit être en premier)
        $this->call(RolePermissionSeeder::class);

        // Utilisateurs de base : admin, personnel…
        $this->call(UserSeeder::class);

        // =========================================================
        // 2. Seeders de DÉMONSTRATION (local + testing uniquement)
        // =========================================================

        if (app()->environment(['local', 'testing'])) {
            // Élèves + responsables (utilise Faker → jamais en prod)
            $this->call(EleveSeeder::class);
        }
    }
}