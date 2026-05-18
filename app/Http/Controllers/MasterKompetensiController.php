<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\DetailKompetensi;
use App\Models\MasterKategori;
use App\Models\MasterKompetensi;
use App\Models\MasterKompetensiPelatihan;
use App\Models\Peran;
use App\Models\Position;
use App\Models\Workunit;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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

    // public function import(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|mimes:xlsx,xls,csv'
    //     ]);

    //     DB::beginTransaction();

    //     try {

    //         $rows = Excel::toArray([], $request->file('file'));

    //         // Sheet pertama
    //         $rows = $rows[0];

    //         /*
    //     |--------------------------------------------------------------------------
    //     | PENAMPUNG ERROR
    //     |--------------------------------------------------------------------------
    //     */

    //         $notFound = [
    //             'kompetensi' => [],
    //             'kategori' => [],
    //             'departement' => [],
    //             'position' => [],
    //             'peran' => [],
    //             'workunit' => [],
    //         ];

    //         $successImport = 0;
    //         $skipImport = 0;

    //         /*
    //     |--------------------------------------------------------------------------
    //     | LOOP DATA
    //     |--------------------------------------------------------------------------
    //     */

    //         foreach (array_slice($rows, 1) as $index => $row) {

    //             $excelRow = $index + 2;

    //             /*
    //         |--------------------------------------------------------------------------
    //         | AMBIL DATA EXCEL
    //         |--------------------------------------------------------------------------
    //         */

    //             $kodeKompetensi = trim($row[0] ?? '');
    //             $kategori       = trim($row[1] ?? '');
    //             $depart         = trim($row[2] ?? '');
    //             $jabatan        = trim($row[3] ?? '');
    //             $posisi         = trim($row[4] ?? '');
    //             $workunit       = trim($row[5] ?? '');

    //             $kodeKompetensi = $kodeKompetensi === '-' ? null : $kodeKompetensi;
    //             $kategori       = $kategori === '-' ? null : $kategori;
    //             $depart         = $depart === '-' ? null : $depart;
    //             $jabatan        = $jabatan === '-' ? null : $jabatan;
    //             $posisi         = $posisi === '-' ? null : $posisi;
    //             $workunit       = $workunit === '-' ? null : $workunit;

    //             $nilaiRaw = trim($row[6] ?? '');

    //             $nilai = null;

    //             if (
    //                 $nilaiRaw !== '' &&
    //                 $nilaiRaw !== '-' &&
    //                 strtolower($nilaiRaw) !== 'null' &&
    //                 strtolower($nilaiRaw) !== 'n/a'
    //             ) {
    //                 $nilai = (int) $nilaiRaw;
    //             }

    //             // Skip jika kode kosong
    //             if (empty($kodeKompetensi)) {
    //                 continue;
    //             }

    //             /*
    //         |--------------------------------------------------------------------------
    //         | MASTER DATA
    //         |--------------------------------------------------------------------------
    //         */

    //             $idKompetensi = MasterKompetensi::whereRaw(
    //                 'LOWER(initial) = ?',
    //                 [strtolower($kodeKompetensi)]
    //             )->value('id');

    //             $idKategori = MasterKategori::whereRaw(
    //                 'LOWER(nama) = ?',
    //                 [strtolower($kategori)]
    //             )->value('id');

    //             $idDepartement = Departement::whereRaw(
    //                 'LOWER("depNama") = ?',
    //                 [strtolower($depart)]
    //             )->value('id');

    //             /*
    //         |--------------------------------------------------------------------------
    //         | FIX MAPPING
    //         |--------------------------------------------------------------------------
    //         | EXCEL:
    //         | JABATAN -> POSITION
    //         | POSISI -> PERAN
    //         |--------------------------------------------------------------------------
    //         */

    //             // POSISI EXCEL -> PERAN
    //             $idPeran = Peran::whereRaw(
    //                 'LOWER(name) = ?',
    //                 [strtolower($posisi)]
    //             )->value('id');

    //             // JABATAN EXCEL -> POSITION
    //             $idPosisi = Position::whereRaw(
    //                 'LOWER("posiNama") = ?',
    //                 [strtolower($jabatan)]
    //             )->value('id');

    //             $idWorkunit = Workunit::whereRaw(
    //                 'LOWER("woruNama") = ?',
    //                 [strtolower($workunit)]
    //             )->value('id');

    //             /*
    //         |--------------------------------------------------------------------------
    //         | KELOMPOKKAN DATA TIDAK DITEMUKAN
    //         |--------------------------------------------------------------------------
    //         */

    //             $hasError = false;

    //             if (!$idKompetensi) {

    //                 $notFound['kompetensi'][] = [
    //                     'row' => $excelRow,
    //                     'value' => $kodeKompetensi
    //                 ];

    //                 $hasError = true;
    //             }

    //             if (!empty($kategori) && !$idKategori) {

    //                 $notFound['kategori'][] = [
    //                     'row' => $excelRow,
    //                     'value' => $kategori
    //                 ];

    //                 $hasError = true;
    //             }

    //             if (!empty($depart) && !$idDepartement) {

    //                 $notFound['departement'][] = [
    //                     'row' => $excelRow,
    //                     'value' => $depart
    //                 ];

    //                 $hasError = true;
    //             }

    //             // JABATAN -> POSITION
    //             if (!empty($jabatan) && !$idPosisi) {

    //                 $notFound['position'][] = [
    //                     'row' => $excelRow,
    //                     'value' => $jabatan
    //                 ];

    //                 $hasError = true;
    //             }

    //             // POSISI -> PERAN
    //             if (!empty($posisi) && !$idPeran) {

    //                 $notFound['peran'][] = [
    //                     'row' => $excelRow,
    //                     'value' => $posisi
    //                 ];

    //                 $hasError = true;
    //             }

    //             if (!empty($workunit) && !$idWorkunit) {

    //                 $notFound['workunit'][] = [
    //                     'row' => $excelRow,
    //                     'value' => $workunit
    //                 ];

    //                 $hasError = true;
    //             }

    //             /*
    //         |--------------------------------------------------------------------------
    //         | SKIP JIKA ADA YANG TIDAK DITEMUKAN
    //         |--------------------------------------------------------------------------
    //         */

    //             if ($hasError) {
    //                 $skipImport++;
    //                 continue;
    //             }

    //             /*
    //         |--------------------------------------------------------------------------
    //         | INSERT / UPDATE
    //         |--------------------------------------------------------------------------
    //         */

    //             MasterKompetensiPelatihan::updateOrCreate(
    //                 [
    //                     'id_kompetensi' => $idKompetensi,
    //                     'id_kategori' => $idKategori,
    //                     'id_departement' => $idDepartement,
    //                     'id_peran' => $idPeran,
    //                     'id_posisi' => $idPosisi,
    //                     'id_workunit' => $idWorkunit,
    //                 ],
    //                 [
    //                     'nilai' => $nilai,
    //                     'user_id' => auth()->id(),
    //                 ]
    //             );

    //             $successImport++;
    //         }

    //         DB::commit();

    //         /*
    //     |--------------------------------------------------------------------------
    //     | JIKA ADA YANG TIDAK DITEMUKAN
    //     |--------------------------------------------------------------------------
    //     */

    //         $hasNotFound =
    //             count($notFound['kompetensi']) > 0 ||
    //             count($notFound['kategori']) > 0 ||
    //             count($notFound['departement']) > 0 ||
    //             count($notFound['position']) > 0 ||
    //             count($notFound['peran']) > 0 ||
    //             count($notFound['workunit']) > 0;

    //         if ($hasNotFound) {

    //             dd([
    //                 'summary' => [
    //                     'success_import' => $successImport,
    //                     'skip_import' => $skipImport,
    //                 ],

    //                 'not_found' => $notFound
    //             ]);
    //         }

    //         return redirect()->back()->with(
    //             'success',
    //             'Import berhasil. Total import: ' . $successImport
    //         );
    //     } catch (\Exception $e) {

    //         DB::rollBack();

    //         dd([
    //             'message' => $e->getMessage(),
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //         ]);
    //     }
    // }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        DB::beginTransaction();

        try {

            $rows = Excel::toArray([], $request->file('file'));

            // Sheet pertama
            $rows = $rows[0];

            /*
        |--------------------------------------------------------------------------
        | PENAMPUNG ERROR
        |--------------------------------------------------------------------------
        */

            $notFound = [
                'kompetensi' => [],
                'kategori' => [],
                'departement' => [],
                'position' => [],
                'peran' => [],
                'workunit' => [],
            ];

            /*
        |--------------------------------------------------------------------------
        | HELPER GROUPING ERROR
        |--------------------------------------------------------------------------
        */

            $addNotFound = function (&$target, $value, $row) {

                if (empty($value)) {
                    return;
                }

                if (!isset($target[$value])) {

                    $target[$value] = [
                        'total' => 0,
                        'rows' => [],
                    ];
                }

                $target[$value]['total']++;
                $target[$value]['rows'][] = $row;
            };

            $successImport = 0;
            $skipImport = 0;

            /*
        |--------------------------------------------------------------------------
        | LOOP DATA
        |--------------------------------------------------------------------------
        */

            foreach (array_slice($rows, 1) as $index => $row) {

                $excelRow = $index + 2;

                /*
            |--------------------------------------------------------------------------
            | AMBIL DATA EXCEL
            |--------------------------------------------------------------------------
            */

                $kodeKompetensi = trim($row[0] ?? '');
                $kategori       = trim($row[1] ?? '');
                $depart         = trim($row[2] ?? '');
                $jabatan        = trim($row[3] ?? '');
                $posisi         = trim($row[4] ?? '');
                $workunit       = trim($row[5] ?? '');

                /*
            |--------------------------------------------------------------------------
            | JIKA "-" MAKA NULL
            |--------------------------------------------------------------------------
            */

                $kodeKompetensi = $kodeKompetensi === '-' ? null : $kodeKompetensi;
                $kategori       = $kategori === '-' ? null : $kategori;
                $depart         = $depart === '-' ? null : $depart;
                $jabatan        = $jabatan === '-' ? null : $jabatan;
                $posisi         = $posisi === '-' ? null : $posisi;
                $workunit       = $workunit === '-' ? null : $workunit;

                /*
            |--------------------------------------------------------------------------
            | NILAI
            |--------------------------------------------------------------------------
            */

                $nilaiRaw = trim($row[6] ?? '');

                $nilai = null;

                if (
                    $nilaiRaw !== '' &&
                    $nilaiRaw !== '-' &&
                    strtolower($nilaiRaw) !== 'null' &&
                    strtolower($nilaiRaw) !== 'n/a'
                ) {
                    $nilai = (int) $nilaiRaw;
                }

                /*
            |--------------------------------------------------------------------------
            | SKIP JIKA KODE KOSONG
            |--------------------------------------------------------------------------
            */

                if (empty($kodeKompetensi)) {
                    continue;
                }

                /*
            |--------------------------------------------------------------------------
            | MASTER DATA
            |--------------------------------------------------------------------------
            */

                $idKompetensi = MasterKompetensi::whereRaw(
                    'LOWER(initial) = ?',
                    [strtolower($kodeKompetensi)]
                )->value('id');

                $idKategori = null;

                if (!empty($kategori)) {

                    $idKategori = MasterKategori::whereRaw(
                        'LOWER(nama) = ?',
                        [strtolower($kategori)]
                    )->value('id');
                }

                $idDepartement = null;

                if (!empty($depart)) {

                    $idDepartement = Departement::whereRaw(
                        'LOWER("depNama") = ?',
                        [strtolower($depart)]
                    )->value('id');
                }

                /*
            |--------------------------------------------------------------------------
            | FIX MAPPING
            |--------------------------------------------------------------------------
            | EXCEL:
            | JABATAN -> POSITION
            | POSISI -> PERAN
            |--------------------------------------------------------------------------
            */

                // POSISI EXCEL -> PERAN
                $idPeran = null;

                if (!empty($posisi)) {

                    $idPeran = Peran::whereRaw(
                        'LOWER(name) = ?',
                        [strtolower($posisi)]
                    )->value('id');
                }

                // JABATAN EXCEL -> POSITION
                $idPosisi = null;

                if (!empty($jabatan)) {

                    $idPosisi = Position::whereRaw(
                        'LOWER("posiNama") = ?',
                        [strtolower($jabatan)]
                    )->value('id');
                }

                $idWorkunit = null;

                if (!empty($workunit)) {

                    $idWorkunit = Workunit::whereRaw(
                        'LOWER("woruNama") = ?',
                        [strtolower($workunit)]
                    )->value('id');
                }

                /*
            |--------------------------------------------------------------------------
            | VALIDASI DATA TIDAK DITEMUKAN
            |--------------------------------------------------------------------------
            */

                $hasError = false;

                /*
            |--------------------------------------------------------------------------
            | KOMPETENSI
            |--------------------------------------------------------------------------
            */

                if (!$idKompetensi) {

                    $addNotFound(
                        $notFound['kompetensi'],
                        $kodeKompetensi,
                        $excelRow
                    );

                    $hasError = true;
                }

                /*
            |--------------------------------------------------------------------------
            | KATEGORI
            |--------------------------------------------------------------------------
            */

                if (!empty($kategori) && !$idKategori) {

                    $addNotFound(
                        $notFound['kategori'],
                        $kategori,
                        $excelRow
                    );

                    $hasError = true;
                }

                /*
            |--------------------------------------------------------------------------
            | DEPARTEMENT
            |--------------------------------------------------------------------------
            */

                if (!empty($depart) && !$idDepartement) {

                    $addNotFound(
                        $notFound['departement'],
                        $depart,
                        $excelRow
                    );

                    $hasError = true;
                }

                /*
            |--------------------------------------------------------------------------
            | POSITION
            |--------------------------------------------------------------------------
            | JABATAN EXCEL -> POSITION
            |--------------------------------------------------------------------------
            */

                if (!empty($jabatan) && !$idPosisi) {

                    $addNotFound(
                        $notFound['position'],
                        $jabatan,
                        $excelRow
                    );

                    $hasError = true;
                }

                /*
            |--------------------------------------------------------------------------
            | PERAN
            |--------------------------------------------------------------------------
            | POSISI EXCEL -> PERAN
            |--------------------------------------------------------------------------
            */

                if (!empty($posisi) && !$idPeran) {

                    $addNotFound(
                        $notFound['peran'],
                        $posisi,
                        $excelRow
                    );

                    $hasError = true;
                }

                /*
            |--------------------------------------------------------------------------
            | WORKUNIT
            |--------------------------------------------------------------------------
            */

                if (!empty($workunit) && !$idWorkunit) {

                    $addNotFound(
                        $notFound['workunit'],
                        $workunit,
                        $excelRow
                    );

                    $hasError = true;
                }

                /*
            |--------------------------------------------------------------------------
            | SKIP JIKA ADA ERROR
            |--------------------------------------------------------------------------
            */

                if ($hasError) {
                    $skipImport++;
                    continue;
                }

                /*
            |--------------------------------------------------------------------------
            | INSERT / UPDATE
            |--------------------------------------------------------------------------
            */

                MasterKompetensiPelatihan::updateOrCreate(
                    [
                        'id_kompetensi' => $idKompetensi,
                        'id_kategori' => $idKategori,
                        'id_departement' => $idDepartement,
                        'id_peran' => $idPeran,
                        'id_posisi' => $idPosisi,
                        'id_workunit' => $idWorkunit,
                    ],
                    [
                        'nilai' => $nilai,
                        'user_id' => auth()->id(),
                    ]
                );

                $successImport++;
            }

            DB::commit();

            /*
        |--------------------------------------------------------------------------
        | CEK ADA ERROR ATAU TIDAK
        |--------------------------------------------------------------------------
        */

            $hasNotFound =
                count($notFound['kompetensi']) > 0 ||
                count($notFound['kategori']) > 0 ||
                count($notFound['departement']) > 0 ||
                count($notFound['position']) > 0 ||
                count($notFound['peran']) > 0 ||
                count($notFound['workunit']) > 0;

            /*
        |--------------------------------------------------------------------------
        | JIKA ADA DATA TIDAK DITEMUKAN
        |--------------------------------------------------------------------------
        */

            if ($hasNotFound) {

                dd([
                    'summary' => [
                        'success_import' => $successImport,
                        'skip_import' => $skipImport,
                    ],

                    'not_found_summary' => $notFound
                ]);
            }

            return redirect()->back()->with(
                'success',
                'Import berhasil. Total import: ' . $successImport
            );
        } catch (\Exception $e) {

            DB::rollBack();

            dd([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
