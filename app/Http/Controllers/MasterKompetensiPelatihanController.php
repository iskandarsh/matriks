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
use Illuminate\Support\Facades\DB;

class MasterKompetensiPelatihanController extends Controller
{
    use AuthorizesRequests;

    protected $activeMenuId;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->activeMenuId = session('active_menu_id');
            return $next($request);
        });
    }

    public function index()
    {
        return view('setting.kompetensi_pelatihan');
    }

    public function create()
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH KATEGORI
    |--------------------------------------------------------------------------
    */
    public function searchKategori(Request $r)
    {
        $q = $r->q;

        $data = MasterKategori::when(
            $q,
            fn($x) => $x->where('nama', 'like', "%$q%")
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
            ->when($q, fn($x) => $x->where('nama', 'like', "%$q%"))
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
    | SEARCH MATERI
    |--------------------------------------------------------------------------
    */
    public function searchMateri(Request $r)
    {
        $q = $r->q;

        $data = TrainingMaterials::query()
            ->when($q, fn($x) => $x->where('title', 'like', "%$q%"))
            ->limit(20)
            ->get();

        return response()->json(
            $data->map(fn($x) => [
                'id' => $x->id,
                'text' => $x->title
            ])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    | Payload dari JS:
    | {
    |   id_jabatan, id_posisi, id_workunit, department_id,
    |   groups: [
    |     { id_kategori, kompetensi_id: [..], detail_kompetensi_id: [..] },
    |     ...
    |   ]
    | }
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_jabatan'    => 'nullable|integer',
            'id_posisi'     => 'nullable|integer',
            'id_workunit'   => 'nullable|integer',
            'department_id' => 'nullable|integer',

            'groups'                          => 'required|array|min:1',
            'groups.*.id_kategori'             => 'required|integer',
            'groups.*.kompetensi_id'           => 'required|array|min:1',
            'groups.*.kompetensi_id.*'         => 'required|integer',
            'groups.*.detail_kompetensi_id'    => 'required|array|min:1',
            'groups.*.detail_kompetensi_id.*'  => 'nullable|integer',
        ]);

        $user = Auth::user();

        $employee = Employee::where('empToken', $user->empToken)->first();

        if (!$employee) {
            return response()->json([
                'message' => 'Employee tidak ditemukan.'
            ], 422);
        }

        $isSuperDepart = $user->departments
            ->pluck('id')
            ->intersect([5, 6])
            ->isNotEmpty();

        $departmentId = $isSuperDepart
            ? ($validated['department_id'] ?? null)
            : $employee->department_id;

        if ($isSuperDepart && empty($departmentId)) {
            return response()->json([
                'message' => 'Department wajib dipilih.'
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($validated['groups'] as $group) {
                foreach ($group['kompetensi_id'] as $index => $kompetensiId) {
                    $detailId = $group['detail_kompetensi_id'][$index] ?? null;

                    // Skip jika nilai belum dipilih
                    if (empty($detailId)) {
                        continue;
                    }

                    MasterKompetensiPelatihan::updateOrCreate(
                        [
                            'id_kategori'    => $group['id_kategori'],
                            'id_kompetensi'  => $kompetensiId,
                            'id_posisi'      => $validated['id_jabatan'] ?? null,
                            'id_peran'       => $validated['id_posisi'] ?? null,
                            'id_workunit'    => $validated['id_workunit'] ?? null,
                            'id_departement' => $departmentId,
                            'user_id'        => $user->id,
                        ],
                        [
                            'nilai' => $detailId,
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data kompetensi berhasil disimpan.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    | $id adalah salah satu baris master_kompetensi_pelatihan.
    | Ambil kombinasi (posisi/peran/workunit/departement/user) dari baris itu,
    | lalu ambil SEMUA baris dengan kombinasi yang sama (lintas kategori),
    | dan kelompokkan jadi groups[] sesuai bentuk yang dibutuhkan JS.
    */
    public function show($id)
    {
        $data = MasterKompetensiPelatihan::with([
            'posisi',
            'peran',
            'workunit',
            'departement',
        ])->findOrFail($id);

        $records = MasterKompetensiPelatihan::with('kategori')
            ->where([
                'id_posisi'      => $data->id_posisi,
                'id_peran'       => $data->id_peran,
                'id_workunit'    => $data->id_workunit,
                'id_departement' => $data->id_departement,
                'user_id'        => $data->user_id,
            ])
            ->get();

        $groups = $records
            ->groupBy('id_kategori')
            ->map(function ($items, $idKategori) {
                return [
                    'id_kategori' => (int) $idKategori,
                    'kategori'    => $items->first()->kategori,
                    'items'       => $items->map(fn($i) => [
                        'kompetensi_id' => $i->id_kompetensi,
                        'nilai'         => $i->nilai,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json([
            'id'             => $data->id,
            'posisi'         => $data->posisi,
            'peran'          => $data->peran,
            'workunit'       => $data->workunit,
            'departement'    => $data->departement,
            'department_id'  => $data->id_departement,
            'groups'         => $groups,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    | Sama seperti store, tapi hapus dulu SEMUA baris lama dengan kombinasi
    | (posisi/peran/workunit/departement/user) yang sama -- lintas kategori --
    | lalu insert ulang dari groups yang dikirim form edit.
    */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_jabatan'    => 'nullable|integer',
            'id_posisi'     => 'nullable|integer',
            'id_workunit'   => 'nullable|integer',
            'department_id' => 'nullable|integer',

            'groups'                          => 'required|array|min:1',
            'groups.*.id_kategori'             => 'required|integer',
            'groups.*.kompetensi_id'           => 'required|array|min:1',
            'groups.*.kompetensi_id.*'         => 'required|integer',
            'groups.*.detail_kompetensi_id'    => 'required|array|min:1',
            'groups.*.detail_kompetensi_id.*'  => 'nullable|integer',
        ]);

        $user = Auth::user();

        $employee = Employee::where('empToken', $user->empToken)->first();
        if (!$employee) {
            return response()->json([
                'message' => 'Employee tidak ditemukan.'
            ], 422);
        }

        $isSuperDepart = $user->departments
            ->pluck('id')
            ->intersect([5, 6])
            ->isNotEmpty();

        $departmentId = $isSuperDepart
            ? ($validated['department_id'] ?? null)
            : $employee->department_id;

        if ($isSuperDepart && empty($departmentId)) {
            return response()->json([
                'message' => 'Department wajib dipilih.'
            ], 422);
        }

        $old = MasterKompetensiPelatihan::findOrFail($id);

        DB::beginTransaction();

        try {
            // Hapus semua baris lama pada kombinasi jabatan/posisi/workunit/department
            // (lintas semua kategori, bukan cuma kategori punya $old)
            MasterKompetensiPelatihan::where([
                'id_posisi'      => $old->id_posisi,
                'id_peran'       => $old->id_peran,
                'id_workunit'    => $old->id_workunit,
                'id_departement' => $old->id_departement,
                'user_id'        => $old->user_id,
            ])->delete();

            // Simpan ulang dari groups[]
            foreach ($validated['groups'] as $group) {
                foreach ($group['kompetensi_id'] as $index => $kompetensiId) {
                    $detailId = $group['detail_kompetensi_id'][$index] ?? null;

                    if (empty($detailId)) {
                        continue;
                    }

                    MasterKompetensiPelatihan::create([
                        'id_kategori'    => $group['id_kategori'],
                        'id_kompetensi'  => $kompetensiId,
                        'id_posisi'      => $validated['id_jabatan'] ?? null,
                        'id_peran'       => $validated['id_posisi'] ?? null,
                        'id_workunit'    => $validated['id_workunit'] ?? null,
                        'id_departement' => $departmentId,
                        'user_id'        => $user->id,
                        'nilai'          => $detailId,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diupdate'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat update data.',
                'error'   => $e->getMessage(),
            ], 500);
        }
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

    public function data()
    {
        $empToken = Auth::user()->empToken;

        $employee = Employee::where('empToken', $empToken)->first();

        $deptId = $employee?->department_id;

        $data = MasterKompetensiPelatihan::with([
            'kompetensi',
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
