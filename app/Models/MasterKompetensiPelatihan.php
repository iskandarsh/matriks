<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterKompetensiPelatihan extends Model
{
    use SoftDeletes;

    protected $table = 'kompetensi_pelatihan';

    protected $fillable = [
        'id_kompetensi',
        'id_materi',
        'id_kategori',   // ✅ tambah ini
        'user_id',
        'id_departement',
        'id_posisi',
        'id_peran',
        'id_workunit',
        'nilai',
    ];

    protected $dates = ['deleted_at'];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'id_departement');
    }

    public function kompetensi()
    {
        return $this->belongsTo(MasterKompetensi::class, 'id_kompetensi');
    }

    public function materi()
    {
        return $this->belongsTo(TrainingMaterials::class, 'id_materi');
    }

    // ✅ RELASI BARU KATEGORI
    public function kategori()
    {
        return $this->belongsTo(MasterKategori::class, 'id_kategori');
    }

    public function posisi()
    {
        return $this->belongsTo(Position::class, 'id_posisi');
    }

    public function peran()
    {
        return $this->belongsTo(Peran::class, 'id_peran');
    }

    public function workunit()
    {
        return $this->belongsTo(Workunit::class, 'id_workunit');
    }
}
