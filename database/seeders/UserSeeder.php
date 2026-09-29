<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@myscoolapp.com',
                'password' => Hash::make('password'), // À changer en production
                'role' => 'super_admin',
            ],
            [
                'name' => 'Administrateur',
                'email' => 'admin@myscoolapp.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Secrétaire',
                'email' => 'secretaire@myscoolapp.com',
                'password' => Hash::make('password'),
                'role' => 'secretaire',
            ],
            [
                'name' => 'Comptable',
                'email' => 'comptable@myscoolapp.com',
                'password' => Hash::make('password'),
                'role' => 'comptable',
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']], // clé de recherche
                $userData                     // champs à créer ou mettre à jour
            );
        }
    }
}