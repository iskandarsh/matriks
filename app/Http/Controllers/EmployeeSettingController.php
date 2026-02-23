<?php

namespace App\Http\Controllers;

use App\Helpers\EncryptHelper;
use App\Models\Employee;
use App\Models\EmployeeSetting;
use App\Models\MasterJabatan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EmployeeSettingController extends Controller
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
        return view('setting.employee_setting');
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
    public function Employeesearch(Request $request)
    {
        $q = $request->q;

        $empToken = Auth::user()->empToken;

        $loginEmployee = Employee::where('empToken', $empToken)->first();

        $deptId = $loginEmployee?->department_id;

        $data = Employee::with('applicant') // ⭐ load relasi applicant
            ->when($deptId, fn($x) => $x->where('department_id', $deptId))
            ->whereNull('empDateout')   // ⭐ hanya employee aktif
            ->limit(20)
            ->get()
            ->filter(function ($emp) use ($q) {

                // kalau applicant kosong skip
                if (!$emp->applicant) return false;

                // decrypt nama
                $name = EncryptHelper::decryptName(
                    $emp->applicant->appNama,
                    $emp->applicant->appToken
                );

                // kalau ada search keyword
                if ($q) {
                    return stripos($name, $q) !== false;
                }

                return true;
            })
            ->map(function ($emp) {

                $name = EncryptHelper::decryptName(
                    $emp->applicant->appNama,
                    $emp->applicant->appToken
                );

                return [
                    'id' => $emp->id,
                    'text' => $name
                ];
            })
            ->values();

        return $data;
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
    public function store(Request $r)
    {
        $r->validate([
            'id_employee' => 'required|integer',
            'id_jabatan' => 'required|integer',
            'type' => 'required|string|max:50',
            'tahun_berlaku' => 'required|integer'
        ]);

        // prevent duplicate jabatan di tahun sama
        $exists = EmployeeSetting::where('id_employee', $r->id_employee)
            ->where('id_jabatan', $r->id_jabatan)
            ->where('tahun_berlaku', $r->tahun_berlaku)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Setting sudah ada untuk tahun ini'
            ], 422);
        }

        $data = EmployeeSetting::create([
            'id_employee' => $r->id_employee,
            'id_jabatan' => $r->id_jabatan,
            'type' => $r->type,
            'tahun_berlaku' => $r->tahun_berlaku,
        ]);

        return response()->json([
            'message' => 'Employee setting berhasil disimpan',
            'data' => $data
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

        $data = EmployeeSetting::with([
            'employee.department',
            'jabatan.departement'
        ])
            ->when($deptId, function ($q) use ($deptId) {

                $q->whereHas('employee', function ($qq) use ($deptId) {
                    $qq->where('department_id', $deptId);
                });
            })
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
