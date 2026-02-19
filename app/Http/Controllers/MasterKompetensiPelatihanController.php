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
    public function store(Request $r)
    {
        // ======================
        // ambil employee via empToken user login
        // ======================
        $empToken = Auth::user()->empToken;

        $employee = Employee::where('empToken', $empToken)->first();

        if (!$employee) {
            return response()->json([
                'message' => 'Employee tidak ditemukan'
            ], 422);
        }

        $departmentId = $employee->department_id;


        // ======================
        // TAGGING kompetensi baru
        // ======================
        if (!is_numeric($r->id_kompetensi)) {

            $k = MasterKompetensi::create([
                'nama' => $r->id_kompetensi,
                'id_kategori' => $r->id_kategori
            ]);

            $kompetensi_id = $k->id;
        } else {

            $kompetensi_id = $r->id_kompetensi;
        }


        // ======================
        // MULTIPLE materi
        // ======================
        foreach ($r->id_materi as $materi) {

            MasterKompetensiPelatihan::create([

                'id_departement' => $departmentId,   // 🔥 dari empToken
                'id_kategori' => $r->id_kategori,
                'id_kompetensi' => $kompetensi_id,
                'id_materi' => $materi

            ]);
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
    public function destroy(MasterKompetensiPelatihan $masterKompetensiPelatihan)
    {
        //
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
        // ambil empToken user login
        $empToken = Auth::user()->empToken;

        // cari employee dari empToken
        $employee = Employee::where('empToken', $empToken)->first();

        // ambil department id
        $deptId = $employee?->department_id;

        $data = MasterKompetensiPelatihan::with([
            'departement',
            'kompetensi',
            'materi'
        ])
            ->when($deptId, fn($q) => $q->where('id_departement', $deptId))
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
