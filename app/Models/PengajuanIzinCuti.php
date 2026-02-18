<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengajuanIzinCuti extends Model
{
    use SoftDeletes;

    protected $table = 'pengajuan_izin_cuti';

    protected $fillable = [
        'id_pengaju',
        'id_manager',
        'is_approved',
        'tanggal_approve',
        'note',
        'note_manager',
        'keputusan',
        'keputusan_id_hrd',
        'tanggal_keputusan',
    ];

    protected $casts = [
        'is_approved'     => 'integer',
        'tanggal_approve' => 'date',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'tanggal_approve',
        'note_manager',
    ];

    // Relasi ke User sebagai pengaju
    public function pengaju()
    {
        return $this->belongsTo(User::class, 'id_pengaju');
    }

    // Relasi ke User sebagai manager
    public function manager()
    {
        return $this->belongsTo(User::class, 'id_manager');
    }
    public function keputusanhrd()
    {
        return $this->belongsTo(User::class, 'keputusan_id_hrd');
    }
    // Relasi ke detail cuti
    public function details()
    {
        return $this->hasMany(PengajuanIzinCutiDetail::class, 'id_pengajuan_izin_cuti');
    }
}
