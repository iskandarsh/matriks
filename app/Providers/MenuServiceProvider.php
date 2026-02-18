<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

use App\Models\Menu;
use App\Models\UserPermission;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    // public function boot(): void
    // {
    //     View::composer('*', function ($view) {
    //         $menus = Menu::whereNull('deleted_at')
    //             ->whereNull('parent_id')
    //             ->with('children')
    //             ->orderBy('order')
    //             ->get();

    //         $view->with('menus', $menus);
    //     });
    // }

    public function boot(): void
    {
        View::composer('*', function ($view) {
            $user = Auth::user();

            if (!$user) {
                $view->with('menus', collect());
                $view->with('activeMenuId', null);
                return;
            }

            $permittedMenuIds = UserPermission::where('user_id', $user->id)
                ->pluck('menu_id')
                ->toArray();

            $menus = Menu::whereNull('deleted_at')
                ->whereNull('parent_id')
                ->where(function ($query) use ($permittedMenuIds) {
                    $query->whereIn('id', $permittedMenuIds)
                        ->orWhereHas('children', fn($q) => $q->whereIn('id', $permittedMenuIds));
                })
                ->with('children') // load semua children dulu
                ->orderBy('order')
                ->get();

            // filter children di PHP supaya hanya yang ada permission
            $menus->each(function ($menu) use ($permittedMenuIds) {
                $menu->children = $menu->children
                    ->filter(fn($child) => in_array($child->id, $permittedMenuIds))
                    ->values();
            });

            $currentRoute = Route::currentRouteName();
            $activeMenuId = null;

            foreach ($menus as $menu) {
                if ($menu->route === $currentRoute) {
                    $activeMenuId = $menu->id;
                    break;
                }
                foreach ($menu->children as $child) {
                    if ($child->route === $currentRoute) {
                        $activeMenuId = $child->id;
                        break 2;
                    }
                }
            }

            Session::put('active_menu_id', $activeMenuId);

            $view->with('menus', $menus);
            $view->with('activeMenuId', $activeMenuId);
        });
    }
}
