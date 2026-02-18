<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; // Import Controller class if you're extending it
use App\Models\AksesUser;
use App\Models\Applicants\Applicant;
use App\Models\Departement;
use App\Models\Employee;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Import AuthorizesRequests
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Menu;
use App\Models\Permission;
use Carbon\Carbon;
use App\Helpers\EncryptHelper;
use App\Models\MasterAbsensi;
use App\Models\PengajuanIzinCutiDetail;
use App\Models\Position;
use App\Models\UserPermission;

class AksesUserController extends Controller
{
    use AuthorizesRequests;
    //


    public function index()
    {
        // $this->authorize('view', AksesUser::class);

        // Ambil semua menu yang bukan parent atau yang punya parent
        $menus_akses = Menu::with('parent')
            ->whereNotNull('parent_id') // hanya anak
            ->orWhereDoesntHave('children') // atau bukan parent
            ->get()
            ->sortBy(function ($menu) {
                // Urut berdasarkan nama parent jika ada, jika tidak pakai nama sendiri
                return strtolower(optional($menu->parent)->name ?? $menu->name);
            });

        return view('user_management.akses_user', compact('menus_akses'));
    }


    public function getQuote()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.forismatic.com/api/1.0/?method=getQuote&lang=en&format=json",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            return response()->json([
                'quoteText' => "Tidak bisa mengambil quote saat ini.",
                'quoteAuthor' => "System",
                'quoteTranslation' => "" // ✅ SKIP TERJEMAH JIKA QUOTE GAGAL
            ], 500);
        }

        curl_close($curl);

        // ✅ Perbaiki JSON yang rusak
        $response = preg_replace('/,\s*}/', '}', $response);
        $response = preg_replace('/,\s*]/', ']', $response);

        $data = json_decode($response, true);

        // ✅ Fallback aman
        $quoteText = $data['quoteText'] ?? "Stay Positive.";
        $quoteAuthor = $data['quoteAuthor'] ?: "Unknown";

        // ✅ Translate Free (try)
        $translate = curl_init();
        curl_setopt_array($translate, [
            CURLOPT_URL => "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=id&dt=t&q=" . urlencode($quoteText),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $translatedResponse = curl_exec($translate);
        curl_close($translate);

        // ✅ Ambil hasil terjemahan aman
        $quoteTranslation = "";
        $jsonTranslation = json_decode($translatedResponse);

        if (is_array($jsonTranslation) && isset($jsonTranslation[0][0][0])) {
            $quoteTranslation = $jsonTranslation[0][0][0];
        }

        // ✅ Jika terjemahan gagal → SKIP (biarkan kosong)
        if (empty($quoteTranslation)) {
            $quoteTranslation = ""; // atau kalau mau bisa: $quoteText;
        }

        return response()->json([
            'quoteText' => $quoteText,
            'quoteAuthor' => $quoteAuthor,
            'quoteTranslation' => $quoteTranslation
        ]);
    }

    public function ajaxUsers(Request $request)
    {
        $menuId = $request->input('menu_id');
        $search = $request->input('search.value'); // DataTables mengirimkan ini untuk pencarian

        // Query user
        $query = User::query()
            ->with([
                'permissions' => function ($q) use ($menuId) {
                    if ($menuId) {
                        $q->where('menu_id', $menuId);
                    }
                },
                'userDepartemen.departemen' // ⬅️ eager load department
            ]);


        // 🔍 Tambahkan kondisi pencarian jika search tidak kosong
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%'); // Bisa ditambah field lain
            });
        }

        // Ambil semua user tanpa pagination
        $users = $query->get();

        // $data = $users->map(function ($user) {
        //     return [
        //         'id' => $user->id,
        //         'name' => $user->name,
        //         'status' => $user->status,
        //         'permissions' => $user->permissions->pluck('id')->toArray(),
        //         'departments' => $user->userDepartemen->map(function ($ud) {
        //             return $ud->departemen->depNama ?? null;
        //         })->filter()->values(), // array nama departemen
        //     ];
        // });

        $data = $users->map(function ($user) {
            // gabung semua departemen user jadi string, misalnya "HR, IT, Finance"
            $departments = $user->userDepartemen->map(function ($ud) {
                return $ud->departemen->depNama ?? null;
            })->filter()->implode(', ');

            return [
                'id' => $user->id,
                'name' => $user->name,
                'status' => $user->status,
                'permissions' => $user->permissions->pluck('id')->toArray(),
                'departments' => $departments, // ✅ string supaya searchable
            ];
        });


        // Get all permissions for the selected menu (optional: bisa disesuaikan)
        $permissions = Permission::all();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $users->count(),
            'recordsFiltered' => $users->count(), // Karena tidak pakai paginate
            'data' => $data,
            'permissions' => $permissions,
        ]);
    }

    public function getAllDeptCuti()
    {
        $userDepartmentIds = auth()->user()
            ->userDepartemen()
            ->pluck('departemen_id');

        // Ambil karyawan aktif sesuai departemen
        $employees = Employee::with('applicant')
            ->whereIn('department_id', $userDepartmentIds)
            ->whereNull('empDateout')
            ->get();

        $data = [];

        foreach ($employees as $emp) {
            // Cek empDatein
            if (!$emp->empDatein) {
                continue; // lewati jika tanggal masuk tidak ada
            }

            $dateIn = Carbon::parse($emp->empDatein);
            $today = Carbon::now();
            $masaKerjaTahun = $dateIn->diffInYears($today);

            // Jika masa kerja < 1 tahun → tidak punya cuti
            if ($masaKerjaTahun < 1) {
                $data[] = [
                    'name' => EncryptHelper::decryptName($emp->applicant->appNama, $emp->applicant->appToken),
                    'total' => 0,
                    'terpakai' => 0
                ];
                continue;
            }

            // Hitung periode ulang tahun kerja tahun ini
            $currentYear = $today->year;
            $start = Carbon::create($currentYear, $dateIn->month, $dateIn->day);
            $end = $start->copy()->addYear()->subDay();

            // Jika ulang tahun belum lewat di tahun ini, geser ke periode sebelumnya
            if ($start->gt($today)) {
                $start->subYear();
                $end->subYear();
            }

            // Ambil jatah cuti maxHari dari absensi_id 3
            $total = MasterAbsensi::where('id', 3)->value('maxHari') ?? 12;

            // Hitung cuti terpakai pada periode ulang tahun
            $terpakai = PengajuanIzinCutiDetail::whereHas('pengajuan', function ($q) use ($emp) {
                $q->where('employee_id', $emp->id)
                    ->where('is_approved', 1);
            })
                ->whereBetween('date', [$start, $end])
                ->where('absensi_id', 3)
                ->count();

            $data[] = [
                'name' => EncryptHelper::decryptName($emp->applicant->appNama, $emp->applicant->appToken),
                'total' => $total,
                'terpakai' => $terpakai
            ];
        }

        return response()->json($data);
    }
    public function updateStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|boolean',
        ]);

        $user = User::find($request->user_id);
        $user->status = $request->status;
        $user->save();

        return response()->json(['message' => 'Status user berhasil diperbarui.']);
    }


    // app/Http/Controllers/UserPermissionController.php

    public function filterByMenu(Request $request)
    {
        $menuId = $request->input('menu_id');

        // Ambil permissions yang terkait dengan menu_id dari pivot table
        $permissions = Permission::all();

        // Ambil semua user dan relasi permission-nya yang sesuai menu
        $users = User::with(['permissions' => function ($query) use ($menuId) {
            $query->wherePivot('menu_id', $menuId);
        }])->get();

        // Format response
        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'permissions' => $user->permissions->pluck('id')->toArray(),
            ];
        });

        // dd($data);
        return response()->json([
            'users' => $data,
            'permissions' => $permissions,
        ]);
    }


    public function updatePermission(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission_id' => 'required|exists:permissions,id',
            'menu_id' => 'required|exists:menus,id',
            'status' => 'required|boolean',
        ]);

        $userId = $request->user_id;
        $permissionId = $request->permission_id;
        $menuId = $request->menu_id;

        if ($request->status) {
            // Cek apakah kombinasi sudah ada di pivot
            $exists = DB::table('user_permissions_matriks')
                ->where('user_id', $userId)
                ->where('permission_id', $permissionId)
                ->where('menu_id', $menuId)
                ->exists();

            if (!$exists) {
                DB::table('user_permissions_matriks')->insert([
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                    'menu_id' => $menuId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            // Hapus relasi jika ada
            DB::table('user_permissions_matriks')
                ->where('user_id', $userId)
                ->where('permission_id', $permissionId)
                ->where('menu_id', $menuId)
                ->delete();
        }

        return response()->json(['message' => 'Permission updated successfully.']);
    }
}
