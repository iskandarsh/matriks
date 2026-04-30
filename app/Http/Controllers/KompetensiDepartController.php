<?php

namespace App\Http\Controllers;

use App\Models\KompetensiDepart;
use App\Models\MasterKompetensi;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KompetensiDepartController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    use AuthorizesRequests;

    protected $activeMenuId;

    public function __construct()
    {
        // Ambil session sekali untuk semua method di controller ini
        $this->middleware(function ($request, $next) {
            $this->activeMenuId = session('active_menu_id');
            return $next($request);
        });
    }

    public function index()
    {

        // $this->authorize('view', [TaxStatuses::class, $this->activeMenuId]);
        return view('master.kompetensi-depart');
    }

    public function data()
    {
        $data = MasterKompetensi::with(['kategori', 'departs'])->get();

        return response()->json([
            'kompetensi' => $data,
            'permissions' => [
                'edit' => true,
                'delete' => true,
            ]
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'kompetensi_ids' => 'required|array',
            'kompetensi_ids.*' => 'exists:kompetensi,id',
            'depart_ids' => 'required|array',
            'depart_ids.*' => 'exists:departments,id',
        ]);

        DB::beginTransaction();

        try {

            foreach ($request->kompetensi_ids as $kompetensiId) {

                $kompetensi = MasterKompetensi::findOrFail($kompetensiId);

                // 🔥 sync tanpa hapus yang lama (biar aman)
                $kompetensi->departs()->syncWithoutDetaching($request->depart_ids);
            }

            DB::commit();

            return response()->json([
                'message' => 'Mapping kompetensi ke depart berhasil disimpan'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menyimpan mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $kompetensi = MasterKompetensi::with('departs')->findOrFail($id);

        return response()->json([
            'id' => $kompetensi->id,
            'nama' => $kompetensi->nama,
            'kompetensi_list' => [
                [
                    'id' => $kompetensi->id,
                    'nama' => $kompetensi->nama
                ]
            ],
            'departs' => $kompetensi->departs->map(function ($d) {
                return [
                    'id' => $d->id,
                    'nama' => $d->depNama
                ];
            })
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KompetensiDepart $kompetensiDepart)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'kompetensi_ids' => 'required|array',
            'depart_ids' => 'required|array',
        ]);

        // hapus mapping lama
        foreach ($request->kompetensi_ids as $kompetensiId) {
            $kompetensi = MasterKompetensi::find($kompetensiId);

            if ($kompetensi) {
                $kompetensi->departs()->sync($request->depart_ids);
            }
        }

        return response()->json([
            'message' => 'Mapping berhasil diupdate'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $kompetensi = MasterKompetensi::findOrFail($id);

            // 🔥 hapus semua relasi depart
            $kompetensi->departs()->detach();

            return response()->json([
                'message' => 'Mapping berhasil dihapus'
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Gagal menghapus mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
