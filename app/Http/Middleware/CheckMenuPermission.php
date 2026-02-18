<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Models\Menu;

class CheckMenuPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $routeName
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next, $routeName)
    {
        // Pastikan user login
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Cari menu berdasarkan route name
        $menu = Menu::where('route', $routeName)->first();

        if (!$menu) {
            abort(404, 'Menu not found');
        }

        // Cek apakah user punya permission untuk menu ini
        $hasPermission = $user->permissions()->where('menu_id', $menu->id)->exists();

        if (!$hasPermission) {
            abort(403, 'You do not have permission to access this menu.');
            // return Redirect::route('dashboard')->with('error', 'You do not have permission to access this menu.');
        }

        return $next($request);
    }
}
