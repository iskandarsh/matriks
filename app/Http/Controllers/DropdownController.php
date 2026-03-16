<?php

namespace App\Http\Controllers;

use App\Models\Peran;
use App\Models\Position;
use App\Models\Workunit;
use Illuminate\Http\Request;

class DropdownController extends Controller
{
    //

    public function select(Request $request, $type)
    {
        $search = $request->q;

        switch ($type) {

            // JABATAN (Position)
            case 'jabatan':
                $query = Position::select('id', 'posiNama as nama');
                break;

            // POSISI (Peran)
            case 'posisi':
                $query = Peran::select('id', 'name as nama');
                break;

            // WORKUNIT
            case 'workunit':
                $query = Workunit::select('id', 'woruNama as nama');
                break;

            default:
                return response()->json([]);
        }

        if ($search) {
            $query->where('nama', 'like', '%' . $search . '%');
        }

        $data = $query->limit(10)->get();

        return response()->json(
            $data->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => $item->nama
                ];
            })
        );
    }
}
