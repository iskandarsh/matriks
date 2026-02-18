<?php

use App\Http\Controllers\AksesUserController;
use App\Http\Controllers\CreateUserController;
use App\Http\Controllers\MenuController;
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
