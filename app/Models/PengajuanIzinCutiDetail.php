<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengajuanIzinCutiDetail extends Model
{


    protected $table = 'pengajuan_izin_cuti_detail';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $fillable = [
        'employee_id',
        'absensi_id',
        'date',
        'id_pengajuan_izin_cuti',
    ];

    public $timestamps = true;

    // Jika kamu ingin, kamu juga bisa tambahkan ini untuk mengatur nama kolom deleted_at (default: deleted_at)
    // const DELETED_AT = 'deleted_at';

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function absensi()
    {
        return $this->belongsTo(MasterAbsensi::class, 'absensi_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function pengajuan()
    {
        return $this->belongsTo(PengajuanIzinCuti::class, 'id_pengajuan_izin_cuti');
    }
}
