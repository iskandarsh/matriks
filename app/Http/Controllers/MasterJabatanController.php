<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\MasterJabatan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MasterJabatanController extends Controller
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
        return view('master.jabatan');
    }

    public function search(Request $request)
    {
        $q = $request->q;

        $empToken = Auth::user()->empToken;

        $employee = Employee::where('empToken', $empToken)->first();

        $deptId = $employee?->department_id;

        $data = MasterJabatan::query()

            ->when($deptId, fn($x) => $x->where('id_departement', $deptId))

            ->when($q, fn($x) => $x->where('nama', 'ilike', "%$q%"))

            ->orderBy('nama')
            ->limit(20)
            ->get();

        return $data->map(fn($d) => [
            'id' => $d->id,
            'text' => $d->nama
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
            'nama' => 'required|string|max:255'
        ]);

        // ambil empToken login
        $empToken = Auth::user()->empToken ?? null;

        if (!$empToken) {
            return response()->json([
                'message' => 'User tidak memiliki empToken'
            ], 422);
        }

        // ambil employee
        $employee = Employee::where('empToken', $empToken)->first();

        if (!$employee || !$employee->department_id) {
            return response()->json([
                'message' => 'Department user tidak ditemukan'
            ], 422);
        }

        // UNIQUE PER DEPARTMENT
        $exists = MasterJabatan::where('nama', $request->nama)
            ->where('id_departement', $employee->department_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Nama jabatan sudah ada di department ini'
            ], 422);
        }

        // create jabatan
        $jabatan = MasterJabatan::create([
            'nama' => $request->nama,
            'id_departement' => $employee->department_id,
        ]);

        return response()->json([
            'message' => 'Jabatan berhasil disimpan',
            'data' => $jabatan
        ]);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $jabatan = MasterJabatan::findOrFail($id);
        return response()->json($jabatan);
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MasterJabatan $masterJabatan)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $jabatan = MasterJabatan::findOrFail($id);

        // ambil dept login
        $empToken = Auth::user()->empToken ?? null;

        $employee = Employee::where('empToken', $empToken)->first();

        $deptId = $employee?->department_id;

        $request->validate([
            'nama' => [
                'required',
                'max:255',
                Rule::unique('jabatan', 'nama')
                    ->where(fn($q) => $q->where('id_departement', $deptId))
                    ->ignore($id)
            ]
        ]);

        $jabatan->update([
            'nama' => $request->nama
        ]);

        return response()->json([
            'message' => 'Jabatan berhasil diupdate'
        ]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function data()
    {
        // ambil employee login
        $empToken = Auth::user()->empToken ?? null;

        $employee = Employee::where('empToken', $empToken)->first();

        $deptId = $employee?->department_id;

        $jabatan = MasterJabatan::with('departement')
            ->when($deptId, fn($q) => $q->where('id_departement', $deptId))
            ->orderBy('nama', 'asc')
            ->get();

        return response()->json([
            'data' => $jabatan,   // 🔥 WAJIB "data"
            'permissions' => [
                'edit' => true,
                'delete' => true
            ]
        ]);
    }

    // Hapus kategori
    public function destroy($id)
    {
        $kategori = MasterJabatan::findOrFail($id);
        $kategori->delete();

        return response()->json([
            'message' => 'Kategori berhasil dihapus'
        ]);
    }
}
