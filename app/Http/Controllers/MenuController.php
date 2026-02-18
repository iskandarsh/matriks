<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MenuController extends Controller
{
    //
    public function setActiveMenu(Request $request)
    {
        $menuId = $request->input('menu_id');
        // simpan ke session
        session(['active_menu_id' => $menuId]);
        return response()->json(['status' => 'ok', 'active' => $menuId]);
    }
}
