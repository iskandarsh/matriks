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

            case 'jabatan':
                $query = Position::select('id', 'posiNama as nama');

                if ($search) {
                    $query->where('posiNama', 'ILIKE', "%$search%");
                }
                break;

            case 'posisi':
                $query = Peran::select('id', 'name as nama');

                if ($search) {
                    $query->where('name', 'ILIKE', "%$search%");
                }
                break;

            case 'workunit':
                $query = Workunit::select('id', 'woruNama as nama');

                if ($search) {
                    $query->where('woruNama', 'ILIKE', "%$search%");
                }
                break;

            default:
                return response()->json([]);
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
