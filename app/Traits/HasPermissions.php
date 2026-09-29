<?php

namespace App\Traits;

use App\Models\Permission;

trait HasPermissions
{
    public function hasPermission($permissionName)
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }
        return false;
    }

    public function hasRole($roleName)
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function givePermissionTo($permissionName)
    {
        $permission = Permission::firstOrCreate(['name' => $permissionName]);
        // Assigner au rôle de l'utilisateur (ou à l'utilisateur directement)
        // On peut décider de donner directement à l'utilisateur ou via son rôle
        // Ici, on donne à tous les rôles de l'utilisateur
        foreach ($this->roles as $role) {
            $role->permissions()->syncWithoutDetaching($permission);
        }
    }
}