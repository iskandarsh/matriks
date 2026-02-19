<?php

namespace App\Http\Controllers;

use App\Models\MasterKategori;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class MasterKategoriController extends Controller
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
        return view('master.kategori');
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
            'nama' => 'required|string|max:255|unique:kategori,nama'
        ]);

        $kategori = MasterKategori::create([
            'nama' => $request->nama
        ]);

        return response()->json([
            'message' => 'Kategori berhasil disimpan',
            'data' => $kategori
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $kategori = MasterKategori::findOrFail($id);

        return response()->json($kategori);
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterKategori $masterKategori)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $kategori = MasterKategori::findOrFail($id);

        $request->validate([
            'nama' => 'required|max:255|unique:kategori,nama,' . $id
        ]);

        $kategori->update([
            'nama' => $request->nama
        ]);

        return response()->json([
            'message' => 'Kategori berhasil diupdate'
        ]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function data()
    {
        $kategori = MasterKategori::orderBy('nama', 'asc')->get();

        $permissions = [
            'edit'   => true,
            'delete' => true,
        ];

        return response()->json([
            'kategori' => $kategori,
            'permissions' => $permissions
        ]);
    }

    // Hapus kategori
    public function destroy($id)
    {
        $kategori = MasterKategori::findOrFail($id);
        $kategori->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}
