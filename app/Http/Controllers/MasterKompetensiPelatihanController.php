<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MasterKategori;
use App\Models\MasterKompetensi;
use App\Models\MasterKompetensiPelatihan;
use App\Models\TrainingMaterials;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterKompetensiPelatihanController extends Controller
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
        return view('setting.kompetensi_pelatihan');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }
    public function searchKategori(Request $r)
    {
        $q = $r->q;

        $data = MasterKategori::when(
            $q,
            fn($x) =>
            $x->where('nama', 'like', "%$q%")
        )
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($x) => [
                'id' => $x->id,
                'text' => $x->nama
            ])
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SEARCH KOMPETENSI (FILTER BY KATEGORI)
    |--------------------------------------------------------------------------
    */
    public function searchKompetensi(Request $r)
    {
        $q = $r->q;


        $data = MasterKompetensi::query()

            ->when(
                $q,
                fn($x) =>
                $x->where('nama', 'like', "%$q%")
            )
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($x) => [
                'id' => $x->id,
                'text' => $x->nama
            ])
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SEARCH MATERI (MULTIPLE SELECT2 + FILTER KOMPETENSI)
    |--------------------------------------------------------------------------
    */
    public function searchMateri(Request $r)
    {
        $q = $r->q;
        // $kompetensi = $r->kompetensi;

        $data = TrainingMaterials::query()

            // ->when(
            //     $kompetensi,
            //     fn($x) =>
            //     $x->where('id_kompetensi', $kompetensi)
            // )

            ->when(
                $q,
                fn($x) =>
                $x->where('title', 'like', "%$q%")
            )

            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($x) => [
                'id' => $x->id,
                'text' => $x->title
            ])
        );
    }
    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $r)
    // {

    //     $r->validate([
    //         'skema' => 'required',
    //         'id_kompetensi' => 'nullable',
    //         'id_materi' => 'required|array'
    //     ]);

    //     $empToken = Auth::user()->empToken;

    //     $employee = Employee::where('empToken', $empToken)->first();

    //     if (!$employee) {
    //         return response()->json([
    //             'message' => 'Employee tidak ditemukan'
    //         ], 422);
    //     }

    //     // ======================
    //     // Tentukan kategori
    //     // ======================
    //     $kategori_id = $r->skema == 'umum'
    //         ? 3
    //         : $r->id_kategori;


    //     // ======================
    //     // TAGGING kompetensi baru
    //     // ======================
    //     if (!is_numeric($r->id_kompetensi)) {

    //         $k = MasterKompetensi::create([
    //             'nama' => $r->id_kompetensi,
    //             'id_kategori' => $kategori_id
    //         ]);

    //         $kompetensi_id = $k->id;
    //     } else {

    //         $kompetensi_id = $r->id_kompetensi;
    //     }


    //     // ======================
    //     // MULTIPLE MATERI
    //     // ======================
    //     foreach ($r->id_materi as $materi) {

    //         $data = [

    //             'id_kategori'    => $kategori_id,
    //             'id_kompetensi'  => $kompetensi_id,
    //             'id_materi'      => $materi,

    //             'user_id'        => Auth::id(),

    //             'id_departement' => $r->skema != 'umum'
    //                 ? $employee->department_id
    //                 : null,

    //             'id_posisi'      => $r->id_jabatan ?? null,
    //             'id_peran'       => $r->id_posisi ?? null,
    //             'id_workunit'    => $r->id_workunit ?? null

    //         ];

    //         // ======================
    //         // CEK DUPLIKAT
    //         // ======================
    //         $exist = MasterKompetensiPelatihan::where($data)->exists();

    //         if ($exist) {
    //             continue;
    //         }

    //         // ======================
    //         // INSERT DATA
    //         // ======================
    //         MasterKompetensiPelatihan::create($data);
    //     }

    //     return response()->json([
    //         'message' => 'Data berhasil disimpan'
    //     ]);
    // }

    public function store(Request $r)
    {
        $r->validate([
            'skema' => 'required',
            'id_kompetensi' => 'nullable',
            'id_materi' => 'required|array',

            'id_jabatan' => 'nullable|array',
            'id_posisi' => 'nullable|array',
            'id_workunit' => 'nullable|array',
        ]);

        $empToken = Auth::user()->empToken;

        $employee = Employee::where('empToken', $empToken)->first();

        if (!$employee) {
            return response()->json([
                'message' => 'Employee tidak ditemukan'
            ], 422);
        }

        // ======================
        // Tentukan kategori
        // ======================
        $kategori_id = $r->skema == 'umum'
            ? 3
            : $r->id_kategori;

        // ======================
        // TAGGING kompetensi baru
        // ======================
        if (!is_numeric($r->id_kompetensi)) {

            $k = MasterKompetensi::create([
                'nama' => $r->id_kompetensi,
                'id_kategori' => $kategori_id
            ]);

            $kompetensi_id = $k->id;
        } else {

            $kompetensi_id = $r->id_kompetensi;
        }

        // ======================
        // NORMALISASI DATA MULTIPLE
        // ======================
        $jabatanList  = $r->id_jabatan ?? [null];
        $peranList    = $r->id_posisi ?? [null];
        $workunitList = $r->id_workunit ?? [null];

        // kalau skema umum → semua null
        if ($r->skema == 'umum') {
            $jabatanList  = [null];
            $peranList    = [null];
            $workunitList = [null];
        }

        // ======================
        // LOOP INSERT (COMBINATION)
        // ======================
        foreach ($r->id_materi as $materi) {

            foreach ($jabatanList as $jabatan) {
                foreach ($peranList as $peran) {
                    foreach ($workunitList as $workunit) {

                        $data = [

                            'id_kategori'    => $kategori_id,
                            'id_kompetensi'  => $kompetensi_id,
                            'id_materi'      => $materi,

                            'user_id'        => Auth::id(),

                            'id_departement' => $r->skema != 'umum'
                                ? $employee->department_id
                                : null,

                            'id_posisi'      => $jabatan,
                            'id_peran'       => $peran,
                            'id_workunit'    => $workunit

                        ];

                        // ======================
                        // CEK DUPLIKAT
                        // ======================
                        $exist = MasterKompetensiPelatihan::where($data)->exists();

                        if ($exist) continue;

                        // ======================
                        // INSERT
                        // ======================
                        MasterKompetensiPelatihan::create($data);
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Data berhasil disimpan'
        ]);
    }
    /**
     * Display the specified resource.
     */
    public function show(MasterKompetensiPelatihan $masterKompetensiPelatihan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterKompetensiPelatihan $masterKompetensiPelatihan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MasterKompetensiPelatihan $masterKompetensiPelatihan)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $data = MasterKompetensiPelatihan::findOrFail($id);
            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus'
            ]);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // public function data()
    // {
    //     $data = MasterKompetensiPelatihan::with([
    //         'departement',
    //         'kompetensi',
    //         'materi'
    //     ])
    //         ->latest()
    //         ->get();

    //     return response()->json([
    //         'data' => $data,
    //         'permissions' => [
    //             'edit' => true,
    //             'delete' => true
    //         ]
    //     ]);
    // }

    public function data()
    {
        $empToken = Auth::user()->empToken;

        $employee = Employee::where('empToken', $empToken)->first();

        $deptId = $employee?->department_id;

        $data = MasterKompetensiPelatihan::with([
            'kompetensi',
            'materi',
            'departement',
            'kategori',
            'posisi',
            'peran',
            'workunit'
        ])
            ->latest()
            ->get();

        return response()->json([
            'data' => $data,
            'permissions' => [
                'edit' => true,
                'delete' => true
            ]
        ]);
    }
}
