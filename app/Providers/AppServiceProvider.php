<?php

namespace App\Providers;

use App\Models\Applicant;
use App\Models\Employee;
// use App\Models\LemburSpl;
// use App\Models\PengajuanIzinCuti;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {

            $user = Auth::user();

            $departmentIds = $user && $user->userDepartemen
                ? $user->userDepartemen->pluck('departemen_id')
                : collect();
            // Count lembur yang belum disetujui (is_approve != 1)
            // $lemburPendingCount = LemburSpl::where(function ($query) {
            //     $query->where('is_approved', 0)
            //         ->orWhereNull('is_approved');
            // })
            //     ->whereIn('departement_id', $departmentIds)
            //     ->count();
            // dd($lemburPendingCount);
            $employeeBaruCount = Employee::whereNull('empRegister')
                ->whereNull('empDateout')
                ->whereHas('applicant', function ($q) {
                    $q->where('appStatusapplicant', 4)
                        ->whereNull('deleted_at');
                })
                ->count();

            $applicantBaruCount = Applicant::where('appStatusapplicant', 0)
                ->whereNull('deleted_at')
                ->count();
            // dd($applicantBaruCount);



            // Count pengajuan cuti/izin yang pending (belum ada keputusan HRD)
            // $cutiIzinPendingCount = PengajuanIzinCuti::whereNull('deleted_at')
            //     ->whereNull('keputusan') // keputusan HRD belum diisi
            //     ->count();


            // dd($departmentIds);
            $recruitmentRequestCount = 0;

            $expiringContractsCount = User::whereHas('employee', function ($q) {
                $q->whereNotNull('empDatetrial')
                    ->whereDate('empDatetrial', '<=', Carbon::now()->addDays(30));
            })->count();
            // dd($employeeBaruCount);
            $view->with('badgeCountsmenu', [
                'employeeBaru' => $employeeBaruCount,
                'applicantBaru' => $applicantBaruCount,

                'recruitment_request' => $recruitmentRequestCount,
                'expiringContracts' => $expiringContractsCount,  // tambah ini
                // 'lemburPending' => $lemburPendingCount,
                // 'cutiIzinPending' => $cutiIzinPendingCount,
            ]);

            $view->with('educationLevels', config('global_variabel.education_levels'));
            $view->with('jenisKartuList', config('global_variabel.jenis_kartu'));
            $view->with('hubunganKeluargaList', config('global_variabel.hubungan_keluarga'));
            $view->with('tingkatanPrestasiList', config('global_variabel.tingkatan_prestasi'));

            $view->with('statusEmployeeList', config('global_variabel.status_employee'));
            $view->with('golonganEmployeeList', config('global_variabel.golongan_employee'));
            $view->with('thrEmployeeList', config('global_variabel.thr_employee'));
            $view->with('bpjsPesertaList', config('global_variabel.bpjs_peserta'));
            $view->with('kelasRawatList', config('global_variabel.kelas_rawat'));
            $view->with('settingEmployeeList', config('global_variabel.setting_employee'));
        });
    }
}
