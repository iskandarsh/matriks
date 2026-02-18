<?php

namespace App\Http\Controllers;

use App\Models\Gedung;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GedungController extends Controller
{
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
        //
        // $this->authorize('view', [Gedung::class, $this->activeMenuId]);
        return view('master.gedung');
    }

    public function getData()
    {
        $positions = Gedung::whereNull('deleted_at')->get();
        $user = Auth::user();

        return response()->json([
            'gedungs' => $positions,
            'permissions' => [
                'edit' => $user->can('edit', [Gedung::class, $this->activeMenuId]),
                'delete' => $user->can('delete', [Gedung::class, $this->activeMenuId]),
            ]
        ]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'geduKode' => 'required|string|max:10|unique:gedungs,geduKode',
            'geduNama' => 'required|string|max:100',
        ]);
        // dd($validated);
        try {
            $gedung = Gedung::create([
                'geduKode' => strtoupper($validated['geduKode']),
                'geduNama' => $validated['geduNama'],
            ]);


            return response()->json([
                'message' => 'Gedung berhasil disimpan.',
                'data' => $gedung
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $gedung = Gedung::findOrFail($id);

        return response()->json($gedung);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'geduKode' => 'required|string|max:10|unique:gedungs,geduKode,' . $id,
            'geduNama' => 'required|string|max:100',
        ]);

        try {
            $gedung = Gedung::findOrFail($id);
            $gedung->update([
                'geduKode' => strtoupper($validated['geduKode']),
                'geduNama' => $validated['geduNama'],
            ]);

            return response()->json([
                'message' => 'Gedung berhasil diperbarui.',
                'data' => $gedung
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui data: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $gedung = Gedung::findOrFail($id); // Akan throw 404 jika tidak ditemukan
            $gedung->delete();

            return response()->json([
                'message' => 'Data gedung berhasil dihapus.'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Data gedung tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
