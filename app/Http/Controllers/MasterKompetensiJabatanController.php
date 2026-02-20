<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MasterKategori;
use App\Models\MasterKompetensi;
use App\Models\MasterKompetensiJabatan;
use App\Models\MasterKompetensiPelatihan;
use App\Models\TrainingMaterials;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterKompetensiJabatanController extends Controller
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
        return view('setting.kompetensi_jabatan');
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
        $empToken = Auth::user()->empToken;

        $employee = Employee::where('empToken', $empToken)->first();

        if (!$employee) {
            return response()->json([
                'message' => 'Employee tidak ditemukan'
            ], 422);
        }

        $r->validate([
            'id_jabatan' => 'required|integer',
            'id_kompetensi' => 'required|integer',
            'skala' => 'required|integer|min:1|max:5'
        ]);

        // cegah double
        $exists = MasterKompetensiJabatan::where([
            'id_jabatan' => $r->id_jabatan,
            'id_kompetensi' => $r->id_kompetensi,
            'id_departement' => $employee->department_id
        ])->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Kompetensi sudah ada pada jabatan ini'
            ], 422);
        }

        MasterKompetensiJabatan::create([
            'id_jabatan' => $r->id_jabatan,
            'id_departement' => $employee->department_id,
            'id_kompetensi' => $r->id_kompetensi,
            'skala' => $r->skala
        ]);

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

            $data = MasterKompetensiJabatan::findOrFail($id);

            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data kompetensi jabatan berhasil dihapus'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function data()
    {
        $empToken = Auth::user()->empToken;

        $employee = Employee::where('empToken', $empToken)->first();

        $deptId = $employee?->department_id;

        $data = MasterKompetensiJabatan::with([
            'departement',
            'jabatan',
            'kompetensi'
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
