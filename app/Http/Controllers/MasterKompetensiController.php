<?php

namespace App\Http\Controllers;

use App\Models\DetailKompetensi;
use App\Models\MasterKategori;
use App\Models\MasterKompetensi;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class MasterKompetensiController extends Controller
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
    public function getSkala($id)
    {
        $data = DetailKompetensi::where('id_kompetensi', $id)
            ->orderBy('skala')
            ->get();

        return response()->json($data);
    }
    public function index()
    {
        // $this->authorize('view', [TaxStatuses::class, $this->activeMenuId]);
        return view('master.kompetensi');
    }

    public function kompetensi(Request $request)
    {
        $user = auth()->user();

        // ambil semua depart user login
        $departIds = $user->departments->pluck('id');

        $data = MasterKompetensi::with('details')
            ->where('kategori_id', $request->kategori_id)

            // 🔥 filter sesuai depart user
            ->whereHas('departs', function ($q) use ($departIds) {
                $q->whereIn('depart_id', $departIds);
            })

            ->get();

        return response()->json($data);
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
            'kategori_id' => 'required|exists:kategori,id', // 🔥 ini baru
            'nama'        => 'required|string|max:255|unique:kompetensi,nama',
            'initial'     => 'required|string|max:10|unique:kompetensi,initial',
            'deskripsi'   => 'nullable|string'
        ]);

        $kompetensi = MasterKompetensi::create([
            'kategori_id' => $request->kategori_id, // 🔥 simpan ke DB
            'nama'        => $request->nama,
            'initial'     => strtoupper($request->initial),
            'deskripsi'   => $request->deskripsi,
        ]);

        return response()->json([
            'message' => 'Kompetensi berhasil disimpan',
            'data'    => $kompetensi
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $kompetensi = MasterKompetensi::with('kategori')->findOrFail($id);

        return response()->json($kompetensi);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterKompetensi $MasterKompetensi)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $kompetensi = MasterKompetensi::findOrFail($id);

        $request->validate([
            'kategori_id' => 'required|exists:kategori,id', // 🔥 baru
            'nama'        => 'required|max:255|unique:kompetensi,nama,' . $id,
            'initial'     => 'required|max:10|unique:kompetensi,initial,' . $id,
            'deskripsi'   => 'nullable|string'
        ]);

        $kompetensi->update([
            'kategori_id' => $request->kategori_id, // 🔥 simpan
            'nama'        => $request->nama,
            'initial'     => strtoupper($request->initial),
            'deskripsi'   => $request->deskripsi,
        ]);

        return response()->json([
            'message' => 'Kompetensi berhasil diupdate'
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function data()
    {
        $kompetensi = MasterKompetensi::with('kategori') // 🔥 ini penting
            ->orderBy('nama', 'asc')
            ->get();

        $permissions = [
            'edit'   => true,
            'delete' => true,
            'proses' => true,
        ];

        return response()->json([
            'kompetensi' => $kompetensi,
            'permissions' => $permissions
        ]);
    }
    public function saveDetail(Request $request, $id)
    {
        DetailKompetensi::where('id_kompetensi', $id)->delete();

        foreach ($request->skala as $i => $skala) {

            if (!$skala) continue;

            DetailKompetensi::create([
                'id_kompetensi' => $id,
                'skala' => $skala,
                'deskripsi' => $request->deskripsi[$i] ?? null
            ]);
        }

        return response()->json([
            'message' => 'Detail kompetensi berhasil disimpan'
        ]);
    }

    public function getDetail($id)
    {
        $details = DetailKompetensi::where('id_kompetensi', $id)
            ->orderBy('skala')
            ->get();

        return response()->json([
            'details' => $details
        ]);
    }

    // Hapus kategori
    public function destroy($id)
    {
        $kategori = MasterKompetensi::findOrFail($id);
        $kategori->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}
