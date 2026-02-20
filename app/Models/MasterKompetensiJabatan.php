<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterKompetensiJabatan extends Model
{
    use SoftDeletes;

    protected $table = 'kompetensi_jabatan';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_jabatan',
        'id_departement',
        'id_kompetensi',
        'skala'
    ];

    protected $dates = ['deleted_at'];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function jabatan()
    {
        return $this->belongsTo(MasterJabatan::class, 'id_jabatan');
    }

    public function departement()
    {
        return $this->belongsTo(Departement::class, 'id_departement');
    }

    public function kompetensi()
    {
        return $this->belongsTo(MasterKompetensi::class, 'id_kompetensi');
    }
}
