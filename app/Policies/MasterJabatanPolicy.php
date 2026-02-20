<?php

namespace App\Policies;

use App\Models\MasterJabatan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MasterJabatanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function view(User $user, int $menuId)
    {

        return $user->permissions()
            ->where('name', 'view')
            ->wherePivot('menu_id', $menuId)
            ->exists();
    }

    public function edit(User $user, int $menuId)
    {
        return $user->permissions()
            ->where('name', 'edit')
            ->wherePivot('menu_id', $menuId)
            ->exists();
    }

    public function delete(User $user, int $menuId)
    {
        return $user->permissions()
            ->where('name', 'delete')
            ->wherePivot('menu_id', $menuId)
            ->exists();
    }

    public function create(User $user, int $menuId)
    {
        return $user->permissions()
            ->where('name', 'create')
            ->wherePivot('menu_id', $menuId)
            ->exists();
    }

    public function import(User $user, int $menuId)
    {
        return $user->permissions()
            ->where('name', 'import')
            ->wherePivot('menu_id', $menuId)
            ->exists();
    }

    public function export(User $user, int $menuId)
    {
        return $user->permissions()
            ->where('name', 'export')
            ->wherePivot('menu_id', $menuId)
            ->exists();
    }

    public function print(User $user, int $menuId)
    {
        return $user->permissions()
            ->where('name', 'print')
            ->wherePivot('menu_id', $menuId)
            ->exists();
    }

    public function process(User $user, int $menuId)
    {
        return $user->permissions()
            ->where('name', 'process')
            ->wherePivot('menu_id', $menuId)
            ->exists();
    }
}
