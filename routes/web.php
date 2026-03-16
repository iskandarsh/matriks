<?php

use App\Http\Controllers\AksesUserController;
use App\Http\Controllers\CreateUserController;
use App\Http\Controllers\DropdownController;
use App\Http\Controllers\EmployeeSettingController;
use App\Http\Controllers\MasterJabatanController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MasterKategoriController;
use App\Http\Controllers\MasterKompetensiController;
use App\Http\Controllers\MasterKompetensiJabatanController;
use App\Http\Controllers\MasterKompetensiPelatihanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SSOLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/get-quote', [AksesUserController::class, 'getQuote'])->name('quote.get');

Route::post('/set-active-menu', [MenuController::class, 'setActiveMenu'])->name('menu.set-active');
Route::prefix('user-management')->group(function () {
    Route::get('/akses_user', [AksesUserController::class, 'index'])->middleware(['auth', 'verified', 'web', 'user.permissions:akses_user'])->name('akses_user');
    Route::get('/create_user', [CreateUserController::class, 'index'])->middleware(['auth', 'verified', 'web', 'user.permissions:create_user'])->name('create_user');
    Route::post('/permissions/update', [AksesUserController::class, 'updatePermission'])->name('user-management.permission.update');
    Route::post('/status-update', [AksesUserController::class, 'updateStatus'])->name('user-management.status.update');
});
Route::get('/ajax-users', [AksesUserController::class, 'ajaxUsers'])->name('user-management.listuser');
Route::resource('kategori', MasterKategoriController::class)->middleware(['auth', 'verified', 'web', 'user.permissions:kategori.index']);
Route::get('/kategori-data', [MasterKategoriController::class, 'data'])->name('kategori.data');
Route::resource('kompetensi', MasterKompetensiController::class)->middleware(['auth', 'verified', 'web', 'user.permissions:kompetensi.index']);
Route::get('/kompetensi-data', [MasterKompetensiController::class, 'data'])->name('kompetensi.data');
Route::resource('ikompetensi_pelatihan', MasterKompetensiPelatihanController::class)->middleware(['auth', 'verified', 'web', 'user.permissions:ikompetensi_pelatihan.index']);
Route::get('/kompetensi_pelatihan-data', [MasterKompetensiPelatihanController::class, 'data'])->name('kompetensi_pelatihan.data');
Route::resource('kompetensi_jabatan', MasterKompetensiJabatanController::class)->middleware(['auth', 'verified', 'web', 'user.permissions:kompetensi_jabatan.index']);
Route::get('/kompetensi_jabatan-data', [MasterKompetensiJabatanController::class, 'data'])->name('kompetensi_jabatan.data');
Route::get('/jabatan/search', [MasterJabatanController::class, 'search'])
    ->name('jabatan.search');
Route::resource('employee_setting', EmployeeSettingController::class)->middleware(['auth', 'verified', 'web', 'user.permissions:employee_setting.index']);
Route::get('/employee_setting-data', [EmployeeSettingController::class, 'data'])->name('employee_setting.data');
Route::get('employee/search', [EmployeeSettingController::class, 'Employeesearch'])
    ->name('employee.search');
Route::get('jabatan/search', [EmployeeSettingController::class, 'search'])
    ->name('jabatan.search');
Route::resource('jabatan', MasterJabatanController::class)->middleware(['auth', 'verified', 'web', 'user.permissions:jabatan.index']);
Route::get('/jabatan-data', [MasterJabatanController::class, 'data'])->name('jabatan.data');
Route::prefix('kompetensi')->name('kompetensi.')->group(function () {

    // Ambil detail untuk modal proses
    Route::get('/{id}/detail', [MasterKompetensiController::class, 'getDetail'])
        ->name('detail');

    // Simpan detail dari modal proses
    Route::post('/{id}/detail', [MasterKompetensiController::class, 'saveDetail'])
        ->name('detail.save');

    Route::get('/{id}/skala', [MasterKompetensiController::class, 'getSkala']);
});


Route::get(
    '/kategori-search',
    [MasterKompetensiPelatihanController::class, 'searchKategori']
)->name('kategori.search');

Route::get(
    '/kompetensi-search',
    [MasterKompetensiPelatihanController::class, 'searchKompetensi']
)->name('kompetensi.search');

Route::get(
    '/materi-search',
    [MasterKompetensiPelatihanController::class, 'searchMateri']
)->name('materi.search');

Route::get('/dropdown/{type}', [DropdownController::class, 'select']);
// SSO Redirect via TOKEN
Route::get('/sso-login-token', [SSOLoginController::class, 'loginWithToken']);

Route::get('/sso-login-web', [SSOLoginController::class, 'showLogin'])->name('sso-login');
Route::post('/sso-login-web', [SSOLoginController::class, 'ssoAuth'])->name('sso-auth');
Route::get('/api/cuti/all-dept', [AksesUserController::class, 'getAllDeptCuti'])
    ->name('cuti.all-dept');

Route::get('/tes123', function () {
    return 'OK';
});

require __DIR__ . '/auth.php';
