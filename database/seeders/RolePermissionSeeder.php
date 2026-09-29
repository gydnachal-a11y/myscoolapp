<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Supprimer tous les rôles non protégés
        Role::whereNotIn('name', ['super_admin', 'admin'])->delete();

        // 2. Créer ou mettre à jour les rôles
        $roles = [
            'super_admin'        => 'Super Administrateur',
            'admin'              => 'Administrateur',
            'directeur'          => 'Directeur',
            'directeur_etudes'   => 'Directeur des Études',
            'secretaire'         => 'Secrétaire',
            'enseignant'         => 'Enseignant',
            'comptable'          => 'Comptable',
            'surveillant'        => 'Surveillant',
            'disciplinaire'      => 'Disciplinaire',
        ];

        $roleModels = [];
        foreach ($roles as $name => $label) {
            $roleModels[$name] = Role::firstOrCreate(
                ['name' => $name],
                ['label' => $label]
            );
        }

        // 3. Attribuer les permissions
        // Super Admin : toutes les permissions
        $roleModels['super_admin']->permissions()->sync(Permission::pluck('id'));

        // Admin : toutes les permissions SAUF gestion des rôles/permissions et tableaux de bord
        $roleModels['admin']->permissions()->sync(
            Permission::whereNotIn('resource', [
                'roles',
                'permissions',
                'statistiques',
                'paiement-dashboard'
            ])->pluck('id')
        );

        // Directeur : vue globale, inscriptions, notes, cours, statistiques (mais pas gestion financière)
        $roleModels['directeur']->permissions()->sync(
            Permission::whereIn('resource', [
                'statistiques',
                'paiement-dashboard',
                'inscriptions',
                'eleves',
                'notes',
                'cours',
                'periode-notes',
                'salles-de-classe',
                'sections',
                'options',
            ])->pluck('id')
        );

        // Directeur des Études : pédagogie, notes, classements
        $roleModels['directeur_etudes']->permissions()->sync(
            Permission::whereIn('resource', [
                'notes',
                'periode-notes',
                'cours',
                'classements',
                'eleves',
                'inscriptions',
                'sections',
                'options',
            ])->pluck('id')
        );

        // Secrétaire : inscriptions, élèves, notes (lecture, création)
        $roleModels['secretaire']->permissions()->sync(
            Permission::whereIn('resource', [
                'inscriptions',
                'eleves',
                'notes',
                'periode-notes',
            ])->pluck('id')
        );

        // Enseignant : voir et saisir notes, consulter ses cours
        $roleModels['enseignant']->permissions()->sync(
            Permission::where(function ($query) {
                $query->where('resource', 'notes')
                      ->whereIn('action', ['index', 'saisie', 'store-mass', 'bulletin', 'classement'])
                  ->orWhere('resource', 'cours')
                  ->whereIn('action', ['index', 'assignements']);
            })->pluck('id')
        );

        // Comptable : finances, paiements, salaires
        $roleModels['comptable']->permissions()->sync(
            Permission::whereIn('resource', [
                'statistiques',
                'paiements',
                'paiement-salaires',
                'avances',
                'frais-supplementaires',
                'paiement-frais-supplementaires',
                'echeances',
                'taux-change',
                'planification-paiements',
                'salaires',
                'salaire-horaires',
            ])->pluck('id')
        );

        // Surveillant : voir élèves, inscriptions, notes (lecture)
        $roleModels['surveillant']->permissions()->sync(
            Permission::whereIn('resource', ['eleves', 'inscriptions', 'notes'])
                ->whereIn('action', ['index', 'show'])
                ->pluck('id')
        );

        // Disciplinaire : voir élèves, inscriptions, notes
        $roleModels['disciplinaire']->permissions()->sync(
            Permission::whereIn('resource', ['eleves', 'inscriptions', 'notes'])
                ->whereIn('action', ['index'])
                ->pluck('id')
        );

        $this->command?->info('Rôles et permissions assignés avec succès.');
    }
}